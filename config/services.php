<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'matrix' => [
        'homeserver_url' => env('MATRIX_HOMESERVER_URL', ''),
        'access_token' => env('MATRIX_ACCESS_TOKEN', ''),
        'room_id' => env('MATRIX_ROOM_ID', ''),
    ],

    'domains' => [
        'cash_full' => env('DOMAIN_CASH_FULL', ''),
        'change_request' => env('DOMAIN_CHANGE_REQUEST', ''),
        'other' => env('DOMAIN_OTHER', ''),
    ],

    'kassenbuch' => [
        'base_url' => env('KASSENBUCH_BASE_URL', ''),
        'username' => env('KASSENBUCH_USERNAME', ''),
        'password' => env('KASSENBUCH_PASSWORD', ''),
        'account' => (int) env('KASSENBUCH_ACCOUNT', 0),
        'entity_name' => env('KASSENBUCH_ENTITY_NAME', 'Wechselstube'),
    ],

    'pretix' => [
        'base_url' => env('PRETIX_BASE_URL', ''),
        'organizer' => env('PRETIX_ORGANIZER', ''),
        'token' => env('PRETIX_API_TOKEN', ''),
        'device_ids' => array_filter(array_map('intval', explode(',', env('PRETIX_DEVICE_IDS', '')))),
        'cashier_ids' => array_filter(array_map('intval', explode(',', env('PRETIX_CASHIER_IDS', '')))),
    ],

];
