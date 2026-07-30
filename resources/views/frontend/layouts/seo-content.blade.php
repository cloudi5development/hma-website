{{--
    Open Graph / Twitter Card tags plus any JSON-LD the admin attached to this
    page (SEO → Page SEO → Social / Schema tabs).

    Precedence per tag: the record's own social field → its meta title/description
    → the page's @section → the site default. Push extra JSON-LD via @push('seo').
--}}
@php
    $metaTitle = $__env->yieldContent('og_title', $__env->yieldContent('title', \App\Models\Setting::get('seo_meta_title', config('app.name'))));
    $metaDesc  = $__env->yieldContent('og_description', $__env->yieldContent('meta_description', \App\Models\Setting::get('seo_meta_description', '')));
    $metaImage = $__env->yieldContent('og_image', \App\Models\Setting::image('seo_default_og_image', \App\Models\Setting::image('site_logo', 'assets/images/branding/logo.png')));

    $ogTitle = $seo?->resolvedOgTitle($metaTitle) ?: $metaTitle;
    $ogDesc  = $seo?->resolvedOgDescription($metaDesc) ?: $metaDesc;
    $ogImage = $seo?->resolvedOgImage($metaImage) ?: $metaImage;

    $twTitle = $seo?->resolvedTwitterTitle($ogTitle) ?: $ogTitle;
    $twDesc  = $seo?->resolvedTwitterDescription($ogDesc) ?: $ogDesc;
    $twImage = $seo?->resolvedTwitterImage($ogImage) ?: $ogImage;
@endphp
<meta property="og:site_name" content="{{ config('app.name') }}">
<meta property="og:title" content="{{ $ogTitle }}">
<meta property="og:description" content="{{ $ogDesc }}">
<meta property="og:type" content="@yield('og_type', 'website')">
<meta property="og:url" content="{{ $seo?->canonical_url ?: url()->current() }}">
<meta property="og:image" content="{{ $ogImage }}">

<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:title" content="{{ $twTitle }}">
<meta name="twitter:description" content="{{ $twDesc }}">
<meta name="twitter:image" content="{{ $twImage }}">

@foreach ($seo?->schemaBlocks() ?? [] as $block)
    <script type="application/ld+json">{!! json_encode($block, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}</script>
@endforeach

@stack('seo')
