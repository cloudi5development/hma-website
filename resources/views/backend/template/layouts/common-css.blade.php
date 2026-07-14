{{--
    Admin panel stylesheets — completely separate from the frontend assets.
    Vendor/library CSS lives in public/backend/template/libs, the theme CSS in
    public/backend/template/css. Page-specific CSS → @push('styles').
--}}
{{-- Libraries (public/backend/template/libs/) --}}
{{-- <link rel="stylesheet" href="{{ asset('backend/template/libs/bootstrap/css/bootstrap.min.css') }}"> --}}

{{-- Admin theme stylesheet --}}
<link rel="stylesheet" href="{{ asset('backend/template/css/style.css') }}">
