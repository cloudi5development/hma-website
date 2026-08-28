<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Content Security Policy
    |--------------------------------------------------------------------------
    |
    | Tells the browser which origins this site is allowed to load code, frames,
    | media and styles from. Anything else — an ad network, a tracker injected
    | through a compromised dependency, a third-party iframe — is refused by the
    | browser before it runs.
    |
    | CSP_ENABLED=false switches it off entirely.
    |
    | CSP_REPORT_ONLY=true sends the policy as Content-Security-Policy-Report-Only
    | instead: nothing is blocked, but every violation is logged to the browser
    | console. Use that after adding a new third-party script to find out what it
    | needs before you enforce it — an enforced policy that is missing an origin
    | breaks the page silently for visitors.
    |
    */

    'enabled' => env('CSP_ENABLED', true),

    'report_only' => env('CSP_REPORT_ONLY', false),

    /*
    |--------------------------------------------------------------------------
    | Allowed origins
    |--------------------------------------------------------------------------
    |
    | Every third party the site actually uses, and nothing else. Adding a new
    | CDN script means adding its host here too, or the browser will drop it.
    |
    */

    'origins' => [

        // Bootstrap, Swiper and TinyMCE all come from jsDelivr; Font Awesome
        // from cdnjs. Both serve stylesheets and webfonts as well as scripts.
        'cdn' => [
            'https://cdn.jsdelivr.net',
            'https://cdnjs.cloudflare.com',
        ],

        // Google Fonts: the stylesheet and the font files are separate hosts.
        'fonts' => [
            'https://fonts.googleapis.com',
            'https://fonts.gstatic.com',
        ],

        // Analytics / Tag Manager, configured under Settings → SEO Defaults.
        // Left in the policy whether or not an ID is set, so switching one on
        // in the panel does not need a code change.
        'analytics' => [
            'https://www.googletagmanager.com',
            'https://www.google-analytics.com',
            'https://region1.google-analytics.com',
        ],

        // The only frames allowed: the branch maps on the contact page and in
        // the admin's branch editor, plus Instagram and YouTube for reels added
        // as a link (Sections → Our Journey). This is the line that stops an ad
        // network dropping an iframe onto the site, so keep it to what is
        // actually used.
        //
        // Instagram serves the embed from instagram.com and redirects some
        // requests through www.instagram.com — both are listed rather than
        // wildcarding the domain. youtube-nocookie is listed alongside
        // youtube.com because YouTube itself redirects between the two.
        'frames' => [
            'https://www.google.com',
            'https://maps.google.com',
            'https://www.instagram.com',
            'https://instagram.com',
            'https://www.youtube.com',
            'https://www.youtube-nocookie.com',
        ],
    ],

];
