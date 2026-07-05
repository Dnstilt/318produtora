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
        'token' => env('POSTMARK_TOKEN'),
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

    'cloudconvert' => [
        'key' => env('CLOUDCONVERT_KEY'),
        'sandbox' => env('CLOUDCONVERT_SANDBOX', false),
    ],

    'ffmpeg' => [
        'ffmpeg' => env('FFMPEG_BINARY', 'ffmpeg'),
        'ffprobe' => env('FFPROBE_BINARY', 'ffprobe'),
        'timeout' => (int) env('FFMPEG_TIMEOUT', 600),
        'threads' => (int) env('FFMPEG_THREADS', 0),
    ],

    'rsync' => [
        'videos' => [
            'source' => env('RSYNC_VIDEOS_SOURCE'),
            'dest' => env('RSYNC_VIDEOS_DEST'),
        ],
        'photos' => [
            'source' => env('RSYNC_PHOTOS_SOURCE'),
            'dest' => env('RSYNC_PHOTOS_DEST'),
        ],
    ],

];
