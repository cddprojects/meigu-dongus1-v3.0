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
        'hz_route_new'
    );


    session_set_cookie_params([

        'lifetime' =>
            0,

        'path' =>
            '/f3/',

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
| CONFIG
|--------------------------------------------------------------------------
*/

$baseDir =
    dirname(
        __DIR__,
        3
    );


$config =
    require
        $baseDir .
        '/private/f3/visitor-secrets.php';


/*
|--------------------------------------------------------------------------
| DATABASE
|--------------------------------------------------------------------------
*/

try {

    $pdo =
        new PDO(

            "mysql:host={$config['db_host']};dbname={$config['db_name']};charset=utf8mb4",

            $config['db_user'],

            $config['db_pass'],

            [

                PDO::ATTR_ERRMODE =>
                    PDO::ERRMODE_EXCEPTION,

                PDO::ATTR_DEFAULT_FETCH_MODE =>
                    PDO::FETCH_ASSOC,

                PDO::ATTR_EMULATE_PREPARES =>
                    false,

            ]
        );


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
| VISITOR IP
|--------------------------------------------------------------------------
*/

function getVisitorIp(): string
{

    $ip =
        $_SERVER[
            'REMOTE_ADDR'
        ]
        ?? '';


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
| FIND ORIGINAL VISITOR ROW
|--------------------------------------------------------------------------
*/

$visitorRow =
    null;


$visitorLogId =

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
| METHOD 1 — SESSION ID
|--------------------------------------------------------------------------
*/

if (
    $visitorLogId > 0
) {

    $stmt =
        $pdo->prepare(
            "

            SELECT
                id

            FROM visitor_logs

            WHERE id =
                :id

            LIMIT 1

            "
        );


    $stmt->execute([

        ':id' =>
            $visitorLogId,

    ]);


    $visitorRow =
        $stmt->fetch()
        ?: null;
}


/*
|--------------------------------------------------------------------------
| METHOD 2 — SAME IP + SAME BROWSER
|--------------------------------------------------------------------------
|
| Handles fast click/session race.
|
| IMPORTANT:
|
| It finds the existing landing-page row.
|
| It does NOT INSERT another row.
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
                id

            FROM visitor_logs

            WHERE visitor_ip =
                :visitor_ip

              AND user_agent =
                :user_agent

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


    if (
        $visitorRow
    ) {

        $_SESSION[
            'visitor_log_id'
        ] =
            (int)
            $visitorRow[
                'id'
            ];
    }
}


/*
|--------------------------------------------------------------------------
| RECORD WHATSAPP CLICK
|--------------------------------------------------------------------------
*/

if (
    $visitorRow
) {

    $stmt =
        $pdo->prepare(
            "

            UPDATE visitor_logs

            SET

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

        ':id' =>
            (int)
            $visitorRow[
                'id'
            ],

    ]);
}


/*
|--------------------------------------------------------------------------
| ONE WHATSAPP NUMBER
|--------------------------------------------------------------------------
*/

$number =
    preg_replace(

        '/\D+/',

        '',

        (string) (
            $config[
                'whatsapp_number'
            ]
            ?? ''
        )
    );


$message =
    trim(

        (string) (
            $config[
                'whatsapp_message'
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

    session_write_close();


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
| FINISH
|--------------------------------------------------------------------------
*/

session_write_close();


header(

    'Location: ' .
    $whatsappUrl,

    true,

    302
);


exit;