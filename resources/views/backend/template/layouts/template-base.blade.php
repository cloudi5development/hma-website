{{--
|--------------------------------------------------------------------------
| Backend (Admin Panel) Master Layout
|--------------------------------------------------------------------------
|
| Every admin page extends this file:
|
|   @extends('backend.template.layouts.template-base')
|   @section('title', 'Dashboard')
|   @section('content') ... @endsection
|   @push('styles') ... @endpush   (page-specific CSS)
|   @push('scripts') ... @endpush  (page-specific JS)
|
--}}
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-layout-mode="light">
<head>
    @include('backend.template.layouts.meta-tags')

    <title>@yield('title', 'Admin') &middot; {{ config('app.name', 'HireMinds') }}</title>

    @include('backend.template.layouts.common-css')
    @stack('styles')
</head>
<body>
    <div id="layout-wrapper">
        @include('backend.template.layouts.header')
        @include('backend.template.layouts.sidebar')

        <div class="main-content">
            <div class="page-content">
                @yield('content')
            </div>

            @include('backend.template.layouts.footer')
        </div>
    </div>

    @include('backend.template.layouts.common-js')
    @stack('scripts')
</body>
</html>
