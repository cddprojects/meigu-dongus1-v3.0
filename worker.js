/**
 * USStockEdge — Cloudflare Worker
 * 
 * Fetches all 20 stock quotes from Finnhub ONCE every 90 seconds,
 * caches the result, and serves all users from cache.
 * 
 * Result: Finnhub only receives ~20 calls/90s regardless of user count.
 * 100+ concurrent users served for free.
 * 
 * Deploy: https://workers.cloudflare.com (free tier = 100,000 req/day)
 */

// SECURITY: API key is stored as a Cloudflare Worker secret.
// Set it via: wrangler secret put FINNHUB_KEY
// Never put the API key directly in this file.
// Access via env.FINNHUB_KEY in handlers below.
const _FINNHUB_KEY_PLACEHOLDER = null; // placeholder — key is read from env
const CACHE_SECONDS = 300;
const CACHE_KEY = 'quotes';

const SYMBOLS = [
  'AAPL','NVDA','MSFT','AMZN','GOOGL','META','TSLA',
  'BRK.B','AVGO','JPM','LLY','UNH','V','XOM','MA',
  'COST','HD','WMT','NFLX','AMD'
];

const corsHeaders = {
  'Content-Type': 'application/json',
  'Access-Control-Allow-Origin': '*',
};

const sleep = (ms) => new Promise((resolve) => setTimeout(resolve, ms));

async function fetchQuote(symbol, apiKey) {
  if (!apiKey) return null;
  try {
    const response = await fetch(
      `https://finnhub.io/api/v1/quote?symbol=${encodeURIComponent(symbol)}&token=${apiKey}`,
      { cf: { cacheTtl: 60, cacheEverything: false } }
    );
    if (!response.ok) return null;
    const data = await response.json();
    return data?.c > 0 ? data : null;
  } catch {
    return null;
  }
}

// Fetch OHLCV candles from Finnhub for the chart section
async function fetchCandles(symbol, resolution, from, to, apiKey) {
  if (!apiKey) return null;
  try {
    const url = `https://finnhub.io/api/v1/stock/candle?symbol=${encodeURIComponent(symbol)}&resolution=${resolution}&from=${from}&to=${to}&token=${apiKey}`;
    const r = await fetch(url);
    if (!r.ok) return null;
    const d = await r.json();
    if (d.s !== 'ok' || !Array.isArray(d.t) || !d.t.length) return { s: 'no_data' };
    return d;
  } catch {
    return null;
  }
}

async function refreshQuotes(env) {
  const apiKey = env.FINNHUB_KEY; // From Wrangler secret
  if (!apiKey) return {};

  const prior = await env.IDEAS_KV.get(CACHE_KEY, 'json');
  const quotes = prior && typeof prior === 'object' ? prior : {};

  for (const symbol of SYMBOLS) {
    const quote = await fetchQuote(symbol, apiKey);
    if (quote) quotes[symbol] = { c: quote.c, d: quote.d, dp: quote.dp, h: quote.h, l: quote.l, o: quote.o, pc: quote.pc };
    await sleep(1200);
  }

  await env.IDEAS_KV.put(CACHE_KEY, JSON.stringify(quotes), {
    expirationTtl: CACHE_SECONDS,
  });

  return quotes;
}

function getCors(request) {
  const origin = request.headers.get('Origin') || '*';
  return {
    'Content-Type': 'application/json',
    'Access-Control-Allow-Origin': origin,
    'Access-Control-Allow-Credentials': 'true',
    'Vary': 'Origin',
  };
}

export default {
  async fetch(request, env, ctx) {
    const url = new URL(request.url);

    if (request.method === 'OPTIONS') {
      const origin = request.headers.get('Origin') || '*';
      return new Response(null, {
        headers: {
          'Access-Control-Allow-Origin': origin,
          'Access-Control-Allow-Methods': 'GET, POST, OPTIONS',
          'Access-Control-Allow-Headers': 'Content-Type',
          'Access-Control-Max-Age': '86400',
          'Vary': 'Origin',
        },
      });
    }

    const ch = getCors(request);

    // GET /quotes — cached stock quote data for the ticker tape
    if (url.pathname === '/quotes' && request.method === 'GET') {
      const cached = await env.IDEAS_KV.get(CACHE_KEY);
      if (cached) {
        return new Response(cached, { headers: { ...ch, 'X-Cache': 'HIT' } });
      }
      ctx.waitUntil(refreshQuotes(env));
      return new Response('{}', { status: 202, headers: { ...ch, 'X-Cache': 'WARMING' } });
    }

    // GET /candle — OHLCV candlestick data for the chart
    if (url.pathname === '/candle' && request.method === 'GET') {
      const apiKey = env.FINNHUB_KEY;
      if (!apiKey) {
        return new Response(JSON.stringify({ error: 'Service unavailable' }), { status: 503, headers: ch });
      }
      const symbol     = url.searchParams.get('symbol');
      const resolution = url.searchParams.get('resolution') || 'D';
      const from       = url.searchParams.get('from');
      const to         = url.searchParams.get('to');

      if (!symbol || !from || !to) {
        return new Response(JSON.stringify({ error: 'Missing required params: symbol, from, to' }), { status: 400, headers: ch });
      }

      // Validate symbol (basic allow-list check)
      const allowed = ['AAPL','MSFT','NVDA','AMZN','GOOGL','META','JPM','V','XOM','WMT','UNH','DIS','KO','AMD','TSLA','LLY','AVGO','MA','COST','BAC','GS','COP'];
      if (!allowed.includes(symbol.toUpperCase())) {
        return new Response(JSON.stringify({ error: 'Symbol not supported' }), { status: 400, headers: ch });
      }

      // Check candle cache (cache key includes symbol+resolution+from+to)
      const cacheKey = `candle_${symbol}_${resolution}_${from}_${to}`;
      const cached = await env.IDEAS_KV.get(cacheKey, { type: 'json' });
      if (cached) {
        return new Response(JSON.stringify(cached), { headers: { ...ch, 'X-Cache': 'HIT' } });
      }

      const data = await fetchCandles(symbol.toUpperCase(), resolution, from, to, apiKey);
      if (!data) {
        return new Response(JSON.stringify({ s: 'error', message: 'Upstream unavailable' }), { status: 502, headers: ch });
      }

      // Cache for 5 minutes for intraday, 1 hour for multi-day
      const ttl = resolution === '5' || resolution === '30' ? 300 : 3600;
      await env.IDEAS_KV.put(cacheKey, JSON.stringify(data), { expirationTtl: ttl });

      return new Response(JSON.stringify(data), { headers: ch });
    }

    // GET / or unknown — basic health check
    return new Response(JSON.stringify({ status: 'ok', endpoints: ['/quotes', '/candle', '/ideas', '/scoreboard'] }), {
      status: 200, headers: ch
    });
  },

  async scheduled(event, env, ctx) {
    ctx.waitUntil(refreshQuotes(env));
  },
};
