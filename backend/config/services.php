<?php

declare(strict_types=1);

return [

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

    'phyllo' => [
        'base_url' => env('PHYLLO_BASE_URL', 'https://api.sandbox.getphyllo.com'),
        'client_id' => env('PHYLLO_CLIENT_ID'),
        'client_secret' => env('PHYLLO_CLIENT_SECRET'),
        'webhook_secret' => env('PHYLLO_WEBHOOK_SECRET'),
        'env' => env('PHYLLO_ENV', 'sandbox'),
        'sdk_token_ttl_minutes' => 30,
    ],

    'youtube' => [
        'api_key' => env('YOUTUBE_API_KEY'),
        'oauth_client_id' => env('YOUTUBE_OAUTH_CLIENT_ID'),
        'oauth_client_secret' => env('YOUTUBE_OAUTH_CLIENT_SECRET'),
    ],

    'meta' => [
        'app_id' => env('META_APP_ID'),
        'app_secret' => env('META_APP_SECRET'),
        'graph_version' => env('META_GRAPH_VERSION', 'v21.0'),
        'webhook_verify_token' => env('META_WEBHOOK_VERIFY_TOKEN'),
    ],

    'x' => [
        'bearer_token' => env('X_BEARER_TOKEN'),
        'max_daily_usd' => (float) env('X_MAX_DAILY_USD', 5),
    ],

    'tiktok' => [
        'client_key' => env('TIKTOK_CLIENT_KEY'),
        'client_secret' => env('TIKTOK_CLIENT_SECRET'),
    ],

    'networks' => [
        'youtube' => filter_var(env('FEATURE_NETWORK_YOUTUBE', true), FILTER_VALIDATE_BOOLEAN),
        'instagram' => filter_var(env('FEATURE_NETWORK_INSTAGRAM', true), FILTER_VALIDATE_BOOLEAN),
        'tiktok' => filter_var(env('FEATURE_NETWORK_TIKTOK', false), FILTER_VALIDATE_BOOLEAN),
        'x' => filter_var(env('FEATURE_NETWORK_X', false), FILTER_VALIDATE_BOOLEAN),
        'facebook' => filter_var(env('FEATURE_NETWORK_FACEBOOK', false), FILTER_VALIDATE_BOOLEAN),
    ],

];
