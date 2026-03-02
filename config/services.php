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

    'gemini' => [
        'api_key' => env('GEMINI_API_KEY'),
        'model' => env('GEMINI_MODEL', 'gemini-1.5-flash'),
        'timeout' => (int) env('GEMINI_TIMEOUT', 90),
        'max_retries' => (int) env('GEMINI_MAX_RETRIES', 3),
        'initial_backoff_ms' => (int) env('GEMINI_INITIAL_BACKOFF_MS', 1200),
        'max_source_chars' => (int) env('GEMINI_MAX_SOURCE_CHARS', 12000),
        'fallback_to_regex' => (bool) env('GEMINI_FALLBACK_REGEX', false),
    ],

    'openai' => [
        'api_key' => env('OPENAI_API_KEY'),
        'model' => env('OPENAI_MODEL', 'gpt-4.1-mini'),
        'timeout' => (int) env('OPENAI_TIMEOUT', 90),
        'max_retries' => (int) env('OPENAI_MAX_RETRIES', 2),
        'initial_backoff_ms' => (int) env('OPENAI_INITIAL_BACKOFF_MS', 1200),
    ],

    'nfce_lookup' => [
        'url_template' => env('NFCE_LOOKUP_URL_TEMPLATE'),
        'token' => env('NFCE_LOOKUP_TOKEN'),
        'token_header' => env('NFCE_LOOKUP_TOKEN_HEADER', 'Authorization'),
        'token_prefix' => env('NFCE_LOOKUP_TOKEN_PREFIX', 'Bearer '),
        'timeout' => (int) env('NFCE_LOOKUP_TIMEOUT', 30),
    ],

];
