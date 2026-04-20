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

    'recaptcha' => [
        'site_key' => env('RECAPTCHA_SITE_KEY', env('GOOGLE_RECAPTCHA_SITE_KEY')),
        'secret_key' => env('RECAPTCHA_SECRET_KEY', env('GOOGLE_RECAPTCHA_SECRET_KEY')),
    ],

    'groq' => [
        'key' => env('GROQ_API_KEY'),
        'model' => env('GROQ_MODEL', 'openai/gpt-oss-20b'),
        'url' => env('GROQ_RESPONSES_URL', 'https://api.groq.com/openai/v1/responses'),
        'timeout' => (int) env('GROQ_TIMEOUT', 30),
        'reasoning_effort' => env('GROQ_REASONING_EFFORT', 'low'),
        'max_output_tokens' => (int) env('GROQ_MAX_OUTPUT_TOKENS', 500),
    ],

];
