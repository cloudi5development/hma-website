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

    /*
    |--------------------------------------------------------------------------
    | Google reCAPTCHA
    |--------------------------------------------------------------------------
    |
    | Guards every public form. Turned on simply by having both keys - an
    | install with neither (a fresh clone, the test suite) behaves exactly as it
    | did before reCAPTCHA existed.
    |
    | The secret belongs in .env and nowhere else. It is never rendered.
    |
    | VERSION MATTERS, AND SO DO THE KEYS: a key is minted as v2 or as v3 in the
    | reCAPTCHA console and the two are not interchangeable. Rendering the v2
    | checkbox with a v3 key gives "ERROR for site owner: Invalid key type", and
    | asking a v2 key for a v3 token fails too. Change this setting only
    | together with the keys.
    |
    |   v2  the "I'm not a robot" checkbox the visitor ticks     (current)
    |   v3  invisible and score-based, nothing to tick
    |
    */
    'recaptcha' => [
        'version'    => env('RECAPTCHA_VERSION', 'v2'),
        'site_key'   => env('RECAPTCHA_SITE_KEY'),
        'secret_key' => env('RECAPTCHA_SECRET_KEY'),

        // v3 ONLY. Google returns 0.0 (almost certainly a bot) to 1.0 (almost
        // certainly a person). 0.5 is Google's own suggested starting point.
        // v2 has no score: ticking the box is the whole answer.
        'min_score'  => (float) env('RECAPTCHA_MIN_SCORE', 0.5),

        // Seconds to wait for Google. If the request itself fails - Google is
        // down, the host has no outbound DNS - the submission is LET THROUGH
        // rather than lost, the same way a mail failure never loses an enquiry.
        // A verdict of "this is a bot" is always obeyed.
        'timeout'    => (int) env('RECAPTCHA_TIMEOUT', 5),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

];
