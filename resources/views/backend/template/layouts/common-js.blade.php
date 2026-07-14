{{--
    Admin panel scripts — completely separate from the frontend assets.
    Library JS lives in public/backend/template/libs, the app script in
    public/backend/template/js. Page-specific JS → @push('scripts').
--}}
{{-- Libraries (public/backend/template/libs/) --}}
{{-- <script src="{{ asset('backend/template/libs/bootstrap/js/bootstrap.bundle.min.js') }}"></script> --}}

{{-- Admin app script --}}
<script src="{{ asset('backend/template/js/app.js') }}"></script>
