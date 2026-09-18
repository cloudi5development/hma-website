{{--
|--------------------------------------------------------------------------
| Frontend Master Layout
|--------------------------------------------------------------------------
|
| Every frontend page extends this file:
|
|   @extends('frontend.layouts.template-base')
|   @section('title', 'About Us')
|   @section('content') ... @endsection
|   @push('styles') ... @endpush   (page-specific CSS)
|   @push('scripts') ... @endpush  (page-specific JS)
|
--}}
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    @include('frontend.layouts.meta-tags')
    @include('frontend.layouts.seo-content')

    {{-- Admin-set meta title (SEO → Page SEO) wins over the page's own @section --}}
    <title>{{ ($seo?->title ?: null)
        ?: $__env->yieldContent('title', \App\Models\Setting::get('seo_meta_title', config('app.name', 'HireMinds'))) }}</title>

    @include('frontend.layouts.common-css')
    @stack('styles')
</head>
<body>
    {{-- First inside <body> so the overlay paints before anything behind it --}}
    @include('frontend.layouts.loader')

    {{-- A page that stands on its own sets @section('standalone') and gets no
         site header or footer — no way from it to the rest of the website.
         Used by the public forms, whose link is sent to students on their own. --}}
    @unless ($__env->hasSection('standalone'))
        @include('frontend.layouts.header')
    @endunless

    <main id="main-content">
        @yield('content')
    </main>

    @unless ($__env->hasSection('standalone'))
        @include('frontend.layouts.footer')
    @endunless
    {{-- @include('frontend.layouts.search-modal') --}}

    @include('frontend.layouts.common-js')
    @stack('scripts')
</body>
</html>
