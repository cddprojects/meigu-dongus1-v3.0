<?php

declare(strict_types=1);


header(
    'Cache-Control: no-store, no-cache, must-revalidate, max-age=0'
);


/*
|--------------------------------------------------------------------------
| SESSION
|--------------------------------------------------------------------------
*/

if (
    session_status()
    !== PHP_SESSION_ACTIVE
) {

    session_name(
        'hz_route'
    );


    session_set_cookie_params([

        'lifetime' =>
            0,

        'path' =>
            '/',

        'secure' =>
            true,

        'httponly' =>
            true,

        'samesite' =>
            'Lax',

    ]);


    session_start();
}


/*
|--------------------------------------------------------------------------
| BASE DIRECTORY
|--------------------------------------------------------------------------
*/

$baseDir =
    dirname(
        __DIR__,
        2
    );


/*
|--------------------------------------------------------------------------
| CONFIG
|--------------------------------------------------------------------------
*/

$config =
    require
        $baseDir .
        '/private/main/visitor-secrets.php';


/*
|--------------------------------------------------------------------------
| TRUSTED ASN LIST
|--------------------------------------------------------------------------
*/

$trustedAsns =
    require
        $baseDir .
        '/private/main/trusted-asns.php';


/*
|--------------------------------------------------------------------------
| ROUTING FUNCTIONS
|--------------------------------------------------------------------------
*/

require_once
    $baseDir .
    '/private/main/visitor-routing.php';


/*
|--------------------------------------------------------------------------
| SAFETY
|--------------------------------------------------------------------------
*/

if (
    !is_array(
        $trustedAsns
    )
) {

    $trustedAsns = [];
}


/*
|--------------------------------------------------------------------------
| DATABASE CONFIG
|--------------------------------------------------------------------------
*/

$dbHost =
    $config['db_host']
    ?? '';

$dbName =
    $config['db_name']
    ?? '';

$dbUser =
    $config['db_user']
    ?? '';

$dbPass =
    $config['db_pass']
    ?? '';

$ipinfoToken =
    $config['ipinfo_token']
    ?? '';


/*
|--------------------------------------------------------------------------
| DATABASE CONNECTION
|--------------------------------------------------------------------------
*/

try {

    $pdo =
        new PDO(

            "mysql:host={$dbHost};dbname={$dbName};charset=utf8mb4",

            $dbUser,

            $dbPass,

            [

                PDO::ATTR_ERRMODE =>
                    PDO::ERRMODE_EXCEPTION,

                PDO::ATTR_DEFAULT_FETCH_MODE =>
                    PDO::FETCH_ASSOC,

                PDO::ATTR_EMULATE_PREPARES =>
                    false,

            ]
        );


    /*
    |--------------------------------------------------------------------------
    | MALAYSIA TIME
    |--------------------------------------------------------------------------
    */

    $pdo->exec(
        "SET time_zone = '+08:00'"
    );


} catch (
    PDOException $e
) {

    http_response_code(500);

    echo
        'Database connection failed.';

    exit;
}


/*
|--------------------------------------------------------------------------
| DATABASE FLAG HELPER
|--------------------------------------------------------------------------
*/

function nullableFlag(
    mixed $value
): ?int {

    if ($value === null) {

        return null;
    }


    return
        ((int) $value) === 1

            ? 1

            : 0;
}


/*
|--------------------------------------------------------------------------
| GET VISITOR IP
|--------------------------------------------------------------------------
*/

function getVisitorIp(): string
{

    $ip =
        $_SERVER['REMOTE_ADDR']
        ?? '';


    /*
    |--------------------------------------------------------------------------
    | CLOUDFLARE REAL IP
    |--------------------------------------------------------------------------
    */

    if (

        !empty(
            $_SERVER[
                'HTTP_CF_CONNECTING_IP'
            ]
        )

        &&

        filter_var(

            $_SERVER[
                'HTTP_CF_CONNECTING_IP'
            ],

            FILTER_VALIDATE_IP
        )

    ) {

        $ip =
            $_SERVER[
                'HTTP_CF_CONNECTING_IP'
            ];
    }


    if (
        !filter_var(
            $ip,
            FILTER_VALIDATE_IP
        )
    ) {

        return '';
    }


    return $ip;
}


/*
|--------------------------------------------------------------------------
| LIVE IPINFO LOOKUP
|--------------------------------------------------------------------------
|
| Used when:
|
| - session row is missing
| - ASN is missing
| - anonymous flag is missing
| - hosting flag is missing
|
| IMPORTANT:
|
| This function DOES NOT create a database row.
|
*/

function lookupRoutingData(
    string $ip,
    string $token
): array {


    $result = [

        'asn' =>
            null,

        'is_anonymous' =>
            null,

        'is_hosting' =>
            null,

    ];


    if (
        $ip === '' ||
        $token === ''
    ) {

        return $result;
    }


    /*
    |--------------------------------------------------------------------------
    | IPINFO CORE URL
    |--------------------------------------------------------------------------
    */

    $url =

        'https://api.ipinfo.io/lookup/' .

        rawurlencode($ip) .

        '?token=' .

        rawurlencode($token);


    /*
    |--------------------------------------------------------------------------
    | CURL
    |--------------------------------------------------------------------------
    */

    $ch =
        curl_init();


    curl_setopt_array(

        $ch,

        [

            CURLOPT_URL =>
                $url,

            CURLOPT_RETURNTRANSFER =>
                true,

            CURLOPT_TIMEOUT =>
                5,

            CURLOPT_CONNECTTIMEOUT =>
                3,

            CURLOPT_SSL_VERIFYPEER =>
                true,

        ]
    );


    $response =
        curl_exec($ch);


    $httpCode =
        (int)
        curl_getinfo(
            $ch,
            CURLINFO_HTTP_CODE
        );


    curl_close($ch);


    /*
    |--------------------------------------------------------------------------
    | FAILED LOOKUP
    |--------------------------------------------------------------------------
    */

    if (

        !is_string(
            $response
        )

        ||

        $response === ''

        ||

        $httpCode !== 200

    ) {

        return $result;
    }


    /*
    |--------------------------------------------------------------------------
    | JSON
    |--------------------------------------------------------------------------
    */

    $data =
        json_decode(
            $response,
            true
        );


    if (
        !is_array(
            $data
        )
    ) {

        return $result;
    }


    /*
    |--------------------------------------------------------------------------
    | ASN
    |--------------------------------------------------------------------------
    */

    $result['asn'] =

        isset(
            $data['as']['asn']
        )

        &&

        is_string(
            $data['as']['asn']
        )

            ? $data['as']['asn']

            : null;


    /*
    |--------------------------------------------------------------------------
    | ANONYMOUS
    |--------------------------------------------------------------------------
    */

    $result[
        'is_anonymous'
    ] =

        array_key_exists(
            'is_anonymous',
            $data
        )

            ? (int)
                (bool)
                $data[
                    'is_anonymous'
                ]

            : null;


    /*
    |--------------------------------------------------------------------------
    | HOSTING
    |--------------------------------------------------------------------------
    */

    $result[
        'is_hosting'
    ] =

        array_key_exists(
            'is_hosting',
            $data
        )

            ? (int)
                (bool)
                $data[
                    'is_hosting'
                ]

            : null;


    return $result;
}


/*
|--------------------------------------------------------------------------
| CURRENT VISITOR
|--------------------------------------------------------------------------
*/

$visitorIp =
    getVisitorIp();


$userAgent =
    $_SERVER[
        'HTTP_USER_AGENT'
    ]
    ?? '';


/*
|--------------------------------------------------------------------------
| SESSION VISITOR ID
|--------------------------------------------------------------------------
*/

$sessionVisitorLogId =

    isset(
        $_SESSION[
            'visitor_log_id'
        ]
    )

        ? (int)
            $_SESSION[
                'visitor_log_id'
            ]

        : 0;


/*
|--------------------------------------------------------------------------
| VISITOR ROW
|--------------------------------------------------------------------------
*/

$visitorRow =
    null;


/*
|--------------------------------------------------------------------------
| METHOD 1
|--------------------------------------------------------------------------
|
| Find visitor using exact session row ID.
|
*/

if (
    $sessionVisitorLogId > 0
) {

    $stmt =
        $pdo->prepare(
            "

            SELECT

                id,

                visitor_ip,

                asn,

                is_anonymous,
                is_hosting,

                whatsapp_route,
                whatsapp_clicked_at

            FROM visitor_logs

            WHERE id = :id

            LIMIT 1

            "
        );


    $stmt->execute([

        ':id' =>
            $sessionVisitorLogId,

    ]);


    $visitorRow =
        $stmt->fetch()
        ?: null;
}


/*
|--------------------------------------------------------------------------
| METHOD 2
|--------------------------------------------------------------------------
|
| Session unavailable:
|
| Find newest unclicked landing-page row using:
|
| - same IP
| - same browser/user-agent
| - within 30 minutes
|
*/

if (

    !$visitorRow

    &&

    $visitorIp !== ''

    &&

    $userAgent !== ''

) {

    $stmt =
        $pdo->prepare(
            "

            SELECT

                id,

                visitor_ip,

                asn,

                is_anonymous,
                is_hosting,

                whatsapp_route,
                whatsapp_clicked_at

            FROM visitor_logs

            WHERE visitor_ip =
                :visitor_ip

              AND user_agent =
                :user_agent

              AND whatsapp_route
                IS NULL

              AND whatsapp_clicked_at
                IS NULL

              AND created_at >=
                (
                    CURRENT_TIMESTAMP
                    - INTERVAL 30 MINUTE
                )

            ORDER BY id DESC

            LIMIT 1

            "
        );


    $stmt->execute([

        ':visitor_ip' =>
            $visitorIp,

        ':user_agent' =>
            $userAgent,

    ]);


    $visitorRow =
        $stmt->fetch()
        ?: null;


    /*
    |--------------------------------------------------------------------------
    | RESTORE SESSION
    |--------------------------------------------------------------------------
    */

    if (
        $visitorRow
    ) {

        $_SESSION[
            'visitor_log_id'
        ] =
            (int)
            $visitorRow['id'];
    }
}


/*
|--------------------------------------------------------------------------
| ROUTING DATA
|--------------------------------------------------------------------------
*/

$asn =
    null;

$isAnonymous =
    null;

$isHosting =
    null;


/*
|--------------------------------------------------------------------------
| USE STORED IPINFO DATA
|--------------------------------------------------------------------------
*/

if (
    $visitorRow
) {

    $asn =

        isset(
            $visitorRow[
                'asn'
            ]
        )

        &&

        is_string(
            $visitorRow[
                'asn'
            ]
        )

            ? $visitorRow[
                'asn'
            ]

            : null;


    $isAnonymous =
        nullableFlag(
            $visitorRow[
                'is_anonymous'
            ]
        );


    $isHosting =
        nullableFlag(
            $visitorRow[
                'is_hosting'
            ]
        );
}


/*
|--------------------------------------------------------------------------
| LIVE IPINFO FALLBACK
|--------------------------------------------------------------------------
|
| Only fills values we do not already have.
|
*/

if (

    $asn === null

    ||

    $isAnonymous === null

    ||

    $isHosting === null

) {

    $liveData =
        lookupRoutingData(

            $visitorIp,

            $ipinfoToken
        );


    if (
        $asn === null
    ) {

        $asn =
            $liveData[
                'asn'
            ];
    }


    if (
        $isAnonymous === null
    ) {

        $isAnonymous =
            $liveData[
                'is_anonymous'
            ];
    }


    if (
        $isHosting === null
    ) {

        $isHosting =
            $liveData[
                'is_hosting'
            ];
    }
}


/*
|--------------------------------------------------------------------------
| DETERMINE ROUTE
|--------------------------------------------------------------------------
|
| Trusted ASN:
|
| → A regardless of anonymous / hosting flags.
|
|
| Non-whitelisted:
|
| anonymous = 0 and hosting = 0
|
| → A
|
| otherwise
|
| → B
|
*/

$route =
    determineWhatsAppRoute(

        $isAnonymous,

        $isHosting,

        $asn,

        $trustedAsns
    );


/*
|--------------------------------------------------------------------------
| SAVE ACTUAL WHATSAPP CLICK
|--------------------------------------------------------------------------
|
| Only UPDATE an existing landing-page row.
|
| Never INSERT a new partial visitor row here.
|
*/

if (
    $visitorRow
) {

    $stmt =
        $pdo->prepare(
            "

            UPDATE visitor_logs

            SET

                whatsapp_route =
                    :whatsapp_route,

                whatsapp_clicked_at =
                    COALESCE(

                        whatsapp_clicked_at,

                        CURRENT_TIMESTAMP
                    )

            WHERE id =
                :id

            "
        );


    $stmt->execute([

        ':whatsapp_route' =>
            $route,

        ':id' =>
            (int)
            $visitorRow[
                'id'
            ],

    ]);
}


/*
|--------------------------------------------------------------------------
| SELECT WHATSAPP NUMBER + MESSAGE
|--------------------------------------------------------------------------
*/

if (
    $route === 'A'
) {

    $numberKey =
        'whatsapp_number_a';

    $messageKey =
        'whatsapp_message_a';


} else {

    $numberKey =
        'whatsapp_number_b';

    $messageKey =
        'whatsapp_message_b';
}


/*
|--------------------------------------------------------------------------
| NUMBER
|--------------------------------------------------------------------------
*/

$number =
    preg_replace(

        '/\D+/',

        '',

        (string) (
            $config[
                $numberKey
            ]
            ?? ''
        )
    );


/*
|--------------------------------------------------------------------------
| MESSAGE
|--------------------------------------------------------------------------
*/

$message =
    trim(

        (string) (
            $config[
                $messageKey
            ]
            ?? ''
        )
    );


/*
|--------------------------------------------------------------------------
| VALIDATE NUMBER
|--------------------------------------------------------------------------
*/

if (

    !is_string(
        $number
    )

    ||

    $number === ''

) {

    http_response_code(500);

    echo
        'WhatsApp destination is not configured.';

    exit;
}


/*
|--------------------------------------------------------------------------
| BUILD WHATSAPP URL
|--------------------------------------------------------------------------
*/

$whatsappUrl =

    'https://wa.me/' .

    $number;


/*
|--------------------------------------------------------------------------
| PREFILLED MESSAGE
|--------------------------------------------------------------------------
*/

if (
    $message !== ''
) {

    $whatsappUrl .=

        '?text=' .

        rawurlencode(
            $message
        );
}


/*
|--------------------------------------------------------------------------
| CLOSE SESSION
|--------------------------------------------------------------------------
*/

session_write_close();


/*
|--------------------------------------------------------------------------
| REDIRECT
|--------------------------------------------------------------------------
*/

header(

    'Location: ' .
    $whatsappUrl,

    true,

    302
);


exit;