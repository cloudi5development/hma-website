{{--
    Open Graph / Twitter Card tags for social sharing. Override per page with
    @section('og_title', '...') etc., or push JSON-LD schema via @push('seo').
--}}
<meta property="og:site_name" content="{{ config('app.name') }}">
<meta property="og:title" content="@yield('og_title', config('app.name'))">
<meta property="og:description" content="@yield('og_description', '')">
<meta property="og:type" content="@yield('og_type', 'website')">
<meta property="og:url" content="{{ url()->current() }}">
<meta property="og:image" content="@yield('og_image', asset('assets/images/branding/logo.png'))">

<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:title" content="@yield('og_title', config('app.name'))">
<meta name="twitter:description" content="@yield('og_description', '')">
<meta name="twitter:image" content="@yield('og_image', asset('assets/images/branding/logo.png'))">

@stack('seo')
