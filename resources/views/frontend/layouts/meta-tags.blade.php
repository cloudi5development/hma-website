{{-- Base document meta: charset, viewport, CSRF token and SEO meta.

     Precedence for every tag below: what the admin set in SEO → Page SEO wins;
     otherwise the page's own @section; otherwise the site-wide default from
     Settings → SEO Defaults. $seo comes from AppServiceProvider's composer. --}}
<meta charset="utf-8">
<meta http-equiv="X-UA-Compatible" content="IE=edge">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="csrf-token" content="{{ csrf_token() }}">

<meta name="description" content="{{ ($seo?->meta_description ?: null)
    ?: $__env->yieldContent('meta_description', \App\Models\Setting::get('seo_meta_description', config('app.name'))) }}">
<meta name="keywords" content="{{ ($seo?->meta_keywords ?: null)
    ?: $__env->yieldContent('meta_keywords', \App\Models\Setting::get('seo_meta_keywords', '')) }}">
<meta name="author" content="{{ config('app.name') }}">
<meta name="robots" content="{{ ($seo?->meta_robots ?: null)
    ?: $__env->yieldContent('meta_robots', 'index, follow') }}">

<link rel="canonical" href="{{ $seo?->canonical_url ?: url()->current() }}">

{{-- Favicon. sizes="any" lets the browser scale the one PNG for every slot;
     apple-touch-icon covers iOS home-screen bookmarks, which ignore rel="icon". --}}
<link rel="icon" type="image/png" sizes="any" href="{{ \App\Models\Setting::image('site_favicon', 'assets/images/branding/favicon.png') }}">
<link rel="apple-touch-icon" href="{{ \App\Models\Setting::image('site_favicon', 'assets/images/branding/favicon.png') }}">
