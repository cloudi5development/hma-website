{{-- Base document meta: charset, viewport, CSRF token and SEO meta.

     Precedence for every tag below: what the admin set in SEO → Page SEO wins;
     otherwise the page's own @section; otherwise the site-wide default from
     Settings → SEO Defaults. $seo comes from AppServiceProvider's composer. --}}
@php
    // See seo-content.blade.php: yieldContent() escapes its default argument,
    // so a fallback passed through it and printed with {{ }} came out escaped
    // twice. hasSection() picks the branch instead.
    $fromSection = fn (string $name, $fallback = '') => $__env->hasSection($name)
        ? $__env->yieldContent($name)
        : $fallback;

    $metaRobots = ($seo?->meta_robots ?: null)
        ?: $fromSection('meta_robots', \App\Models\Setting::get('seo_meta_robots', 'index, follow'));
    $googleSiteVerification = trim((string) \App\Models\Setting::get('seo_google_site_verification', ''));
    $bingSiteVerification = trim((string) \App\Models\Setting::get('seo_bing_site_verification', ''));
    $googleAnalyticsId = trim((string) \App\Models\Setting::get('seo_google_analytics_id', ''));
    $googleTagManagerId = trim((string) \App\Models\Setting::get('seo_google_tag_manager_id', ''));
@endphp

<meta charset="utf-8">
<meta http-equiv="X-UA-Compatible" content="IE=edge">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="csrf-token" content="{{ csrf_token() }}">

<meta name="description" content="{{ ($seo?->meta_description ?: null)
    ?: $fromSection('meta_description', \App\Models\Setting::get('seo_meta_description', config('app.name'))) }}">
<meta name="keywords" content="{{ ($seo?->meta_keywords ?: null)
    ?: $fromSection('meta_keywords', \App\Models\Setting::get('seo_meta_keywords', '')) }}">
<meta name="author" content="{{ config('app.name') }}">
<meta name="robots" content="{{ $metaRobots }}">
@if ($googleSiteVerification !== '')
    <meta name="google-site-verification" content="{{ $googleSiteVerification }}">
@endif
@if ($bingSiteVerification !== '')
    <meta name="msvalidate.01" content="{{ $bingSiteVerification }}">
@endif

<link rel="canonical" href="{{ $seo?->canonical_url ?: url()->current() }}">

@if ($googleAnalyticsId !== '')
    <script async src="https://www.googletagmanager.com/gtag/js?id={{ $googleAnalyticsId }}"></script>
    <script>
        window.dataLayer = window.dataLayer || [];
        function gtag(){dataLayer.push(arguments);}
        gtag('js', new Date());
        gtag('config', '{{ $googleAnalyticsId }}');
    </script>
@endif

@if ($googleTagManagerId !== '')
    <script>
        (function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start':new Date().getTime(),event:'gtm.js'});var f=d.getElementsByTagName(s)[0],j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';j.async=true;j.src='https://www.googletagmanager.com/gtm.js?id='+i+dl;f.parentNode.insertBefore(j,f);})(window,document,'script','dataLayer','{{ $googleTagManagerId }}');
    </script>
@endif

{{-- Favicon. sizes="any" lets the browser scale the one PNG for every slot;
     apple-touch-icon covers iOS home-screen bookmarks, which ignore rel="icon". --}}
<link rel="icon" type="image/png" sizes="any" href="{{ \App\Models\Setting::image('site_favicon', 'assets/images/branding/favicon.png') }}">
<link rel="apple-touch-icon" href="{{ \App\Models\Setting::image('site_favicon', 'assets/images/branding/favicon.png') }}">
