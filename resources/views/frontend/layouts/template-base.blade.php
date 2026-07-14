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

    <title>@yield('title', config('app.name', 'HireMinds'))</title>

    @include('frontend.layouts.common-css')
    @stack('styles')
</head>
<body>
    @include('frontend.layouts.header')

    <main id="main-content">
        @yield('content')
    </main>

    @include('frontend.layouts.footer')
    {{-- @include('frontend.layouts.search-modal') --}}

    @include('frontend.layouts.common-js')
    @stack('scripts')
</body>
</html>
