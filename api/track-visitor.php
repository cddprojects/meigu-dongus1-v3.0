<?php

declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');


/*
|--------------------------------------------------------------------------
| SESSION
|--------------------------------------------------------------------------
*/

if (session_status() !== PHP_SESSION_ACTIVE) {

    session_name('hz_route');

    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'secure' => true,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);

    session_start();
}


/*
|--------------------------------------------------------------------------
| CONFIG
|--------------------------------------------------------------------------
*/

$config = require dirname(__DIR__, 2) .
    '/private/main/visitor-secrets.php';

$dbHost = $config['db_host'] ?? '';
$dbName = $config['db_name'] ?? '';
$dbUser = $config['db_user'] ?? '';
$dbPass = $config['db_pass'] ?? '';

$ipinfoToken = $config['ipinfo_token'] ?? '';


/*
|--------------------------------------------------------------------------
| DATABASE CONNECTION
|--------------------------------------------------------------------------
*/

try {

    $pdo = new PDO(
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


} catch (PDOException $e) {

    http_response_code(500);

    echo json_encode([
        'success' => false,
        'message' => 'Database connection failed',
    ]);

    exit;
}


/*
|--------------------------------------------------------------------------
| VISITOR IP
|--------------------------------------------------------------------------
*/

$ip =
    $_SERVER['REMOTE_ADDR']
    ?? '';


/*
|--------------------------------------------------------------------------
| CLOUDFLARE REAL IP
|--------------------------------------------------------------------------
*/

if (
    !empty($_SERVER['HTTP_CF_CONNECTING_IP']) &&
    filter_var(
        $_SERVER['HTTP_CF_CONNECTING_IP'],
        FILTER_VALIDATE_IP
    )
) {

    $ip =
        $_SERVER['HTTP_CF_CONNECTING_IP'];
}


if (
    !filter_var(
        $ip,
        FILTER_VALIDATE_IP
    )
) {

    http_response_code(400);

    echo json_encode([
        'success' => false,
        'message' => 'Invalid visitor IP',
    ]);

    exit;
}


/*
|--------------------------------------------------------------------------
| GET DATA FROM LANDING PAGE
|--------------------------------------------------------------------------
*/

$input =
    json_decode(
        file_get_contents('php://input'),
        true
    );


if (!is_array($input)) {
    $input = [];
}


$pageUrl =
    $input['page_url']
    ?? null;

$referrer =
    $input['referrer']
    ?? null;

$utmSource =
    $input['utm_source']
    ?? null;

$utmMedium =
    $input['utm_medium']
    ?? null;

$utmCampaign =
    $input['utm_campaign']
    ?? null;


$userAgent =
    $_SERVER['HTTP_USER_AGENT']
    ?? null;


/*
|--------------------------------------------------------------------------
| DEFAULT IPINFO DATA
|--------------------------------------------------------------------------
*/

$countryCode = null;
$country = null;

$city = null;
$region = null;
$regionCode = null;
$postalCode = null;

$latitude = null;
$longitude = null;
$timezone = null;


$asn = null;
$asName = null;
$asDomain = null;
$asType = null;


$isAnonymous = null;
$isHosting = null;
$isMobile = null;
$isAnycast = null;
$isSatellite = null;


/*
|--------------------------------------------------------------------------
| IPINFO CORE LOOKUP
|--------------------------------------------------------------------------
*/

if ($ipinfoToken !== '') {

    $ipinfoUrl =

        'https://api.ipinfo.io/lookup/' .

        rawurlencode($ip) .

        '?token=' .

        rawurlencode($ipinfoToken);


    $ch =
        curl_init();


    curl_setopt_array(
        $ch,
        [

            CURLOPT_URL =>
                $ipinfoUrl,

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
        (int) curl_getinfo(
            $ch,
            CURLINFO_HTTP_CODE
        );


    curl_close($ch);


    /*
    |--------------------------------------------------------------------------
    | READ IPINFO RESPONSE
    |--------------------------------------------------------------------------
    */

    if (
        is_string($response) &&
        $response !== '' &&
        $httpCode === 200
    ) {

        $data =
            json_decode(
                $response,
                true
            );


        if (is_array($data)) {


            /*
            |--------------------------------------------------------------------------
            | GEO
            |--------------------------------------------------------------------------
            */

            $countryCode =
                $data['geo']['country_code']
                ?? null;

            $country =
                $data['geo']['country']
                ?? null;

            $city =
                $data['geo']['city']
                ?? null;

            $region =
                $data['geo']['region']
                ?? null;

            $regionCode =
                $data['geo']['region_code']
                ?? null;

            $postalCode =
                $data['geo']['postal_code']
                ?? null;

            $latitude =
                $data['geo']['latitude']
                ?? null;

            $longitude =
                $data['geo']['longitude']
                ?? null;

            $timezone =
                $data['geo']['timezone']
                ?? null;


            /*
            |--------------------------------------------------------------------------
            | ASN / ISP
            |--------------------------------------------------------------------------
            */

            $asn =
                $data['as']['asn']
                ?? null;

            $asName =
                $data['as']['name']
                ?? null;

            $asDomain =
                $data['as']['domain']
                ?? null;

            $asType =
                $data['as']['type']
                ?? null;


            /*
            |--------------------------------------------------------------------------
            | NETWORK FLAGS
            |--------------------------------------------------------------------------
            */

            $isAnonymous =

                array_key_exists(
                    'is_anonymous',
                    $data
                )

                    ? (int) (bool)
                        $data['is_anonymous']

                    : null;


            $isHosting =

                array_key_exists(
                    'is_hosting',
                    $data
                )

                    ? (int) (bool)
                        $data['is_hosting']

                    : null;


            $isMobile =

                array_key_exists(
                    'is_mobile',
                    $data
                )

                    ? (int) (bool)
                        $data['is_mobile']

                    : null;


            $isAnycast =

                array_key_exists(
                    'is_anycast',
                    $data
                )

                    ? (int) (bool)
                        $data['is_anycast']

                    : null;


            $isSatellite =

                array_key_exists(
                    'is_satellite',
                    $data
                )

                    ? (int) (bool)
                        $data['is_satellite']

                    : null;
        }
    }
}


/*
|--------------------------------------------------------------------------
| INSERT VISITOR
|--------------------------------------------------------------------------
|
| IMPORTANT:
|
| No A/B decision here.
|
| Visitor has not clicked WhatsApp yet.
|
| whatsapp_route      = NULL
| whatsapp_clicked_at = NULL
|
*/

$sql = "

    INSERT INTO visitor_logs (

        visitor_ip,

        country_code,
        country,

        city,
        region,
        region_code,
        postal_code,

        latitude,
        longitude,
        timezone,

        asn,
        as_name,
        as_domain,
        as_type,

        is_anonymous,
        is_hosting,
        is_mobile,
        is_anycast,
        is_satellite,

        whatsapp_route,
        whatsapp_clicked_at,

        page_url,
        referrer,
        user_agent,

        utm_source,
        utm_medium,
        utm_campaign

    ) VALUES (

        :visitor_ip,

        :country_code,
        :country,

        :city,
        :region,
        :region_code,
        :postal_code,

        :latitude,
        :longitude,
        :timezone,

        :asn,
        :as_name,
        :as_domain,
        :as_type,

        :is_anonymous,
        :is_hosting,
        :is_mobile,
        :is_anycast,
        :is_satellite,

        NULL,
        NULL,

        :page_url,
        :referrer,
        :user_agent,

        :utm_source,
        :utm_medium,
        :utm_campaign
    )

";


try {

    $stmt =
        $pdo->prepare($sql);


    $stmt->execute([

        ':visitor_ip' =>
            $ip,


        ':country_code' =>
            $countryCode,

        ':country' =>
            $country,


        ':city' =>
            $city,

        ':region' =>
            $region,

        ':region_code' =>
            $regionCode,

        ':postal_code' =>
            $postalCode,


        ':latitude' =>
            $latitude,

        ':longitude' =>
            $longitude,

        ':timezone' =>
            $timezone,


        ':asn' =>
            $asn,

        ':as_name' =>
            $asName,

        ':as_domain' =>
            $asDomain,

        ':as_type' =>
            $asType,


        ':is_anonymous' =>
            $isAnonymous,

        ':is_hosting' =>
            $isHosting,

        ':is_mobile' =>
            $isMobile,

        ':is_anycast' =>
            $isAnycast,

        ':is_satellite' =>
            $isSatellite,


        ':page_url' =>
            $pageUrl,

        ':referrer' =>
            $referrer,

        ':user_agent' =>
            $userAgent,


        ':utm_source' =>
            $utmSource,

        ':utm_medium' =>
            $utmMedium,

        ':utm_campaign' =>
            $utmCampaign,

    ]);


    /*
    |--------------------------------------------------------------------------
    | SAVE ORIGINAL ROW ID FOR WHATSAPP CLICK
    |--------------------------------------------------------------------------
    */

    $_SESSION['visitor_log_id'] =
        (int) $pdo->lastInsertId();


} catch (PDOException $e) {

    http_response_code(500);

    echo json_encode([
        'success' => false,
        'message' => 'Failed to save visitor',
    ]);

    exit;
}


/*
|--------------------------------------------------------------------------
| FINISH
|--------------------------------------------------------------------------
*/

session_write_close();


echo json_encode([
    'success' => true,
]);


exit;