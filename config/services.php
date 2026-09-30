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

    'openai' => [
        'api_key' => env('OPENAI_API_KEY'),
        'base_url' => env('OPENAI_BASE_URL', 'https://api.openai.com/v1'),
        'model' => env('OPENAI_DEFAULT_MODEL', 'gpt-4o-mini'),
    ],

    'ollama' => [
        'base_url' => env('OLLAMA_BASE_URL', 'http://127.0.0.1:11434'),
        'model' => env('OLLAMA_DEFAULT_MODEL', 'llama3.1'),
    ],

    'social_platforms' => [
        'facebook' => [
            'client_id' => env('FACEBOOK_CLIENT_ID'),
            'client_secret' => env('FACEBOOK_CLIENT_SECRET'),
            'redirect_uri' => env('FACEBOOK_REDIRECT_URI'),
            'scopes' => ['pages_manage_posts', 'pages_read_engagement', 'pages_show_list'],
        ],
        'instagram' => [
            'client_id' => env('INSTAGRAM_CLIENT_ID'),
            'client_secret' => env('INSTAGRAM_CLIENT_SECRET'),
            'redirect_uri' => env('INSTAGRAM_REDIRECT_URI'),
            'scopes' => ['instagram_basic', 'instagram_content_publish', 'instagram_manage_insights'],
        ],
        'x' => [
            'client_id' => env('X_CLIENT_ID'),
            'client_secret' => env('X_CLIENT_SECRET'),
            'redirect_uri' => env('X_REDIRECT_URI'),
            'scopes' => ['tweet.read', 'tweet.write', 'users.read', 'offline.access'],
        ],
        'pinterest' => [
            'client_id' => env('PINTEREST_CLIENT_ID'),
            'client_secret' => env('PINTEREST_CLIENT_SECRET'),
            'redirect_uri' => env('PINTEREST_REDIRECT_URI'),
            'scopes' => ['boards:read', 'pins:read', 'pins:write', 'user_accounts:read'],
        ],
        'youtube' => [
            'client_id' => env('YOUTUBE_CLIENT_ID'),
            'client_secret' => env('YOUTUBE_CLIENT_SECRET'),
            'redirect_uri' => env('YOUTUBE_REDIRECT_URI'),
            'scopes' => ['https://www.googleapis.com/auth/youtube.upload', 'https://www.googleapis.com/auth/youtube.readonly'],
        ],
        'linkedin' => [
            'client_id' => env('LINKEDIN_CLIENT_ID'),
            'client_secret' => env('LINKEDIN_CLIENT_SECRET'),
            'redirect_uri' => env('LINKEDIN_REDIRECT_URI'),
            'scopes' => ['openid', 'profile', 'w_member_social'],
        ],
    ],

    'webhooks' => [
        'signing_secret' => env('WEBHOOK_SIGNING_SECRET'),
        'facebook_secret' => env('FACEBOOK_WEBHOOK_SECRET'),
        'x_secret' => env('X_WEBHOOK_SECRET'),
    ],

];
