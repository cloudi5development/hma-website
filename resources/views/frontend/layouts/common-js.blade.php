{{--
    Global frontend scripts, loaded at the end of <body>. Vendor libraries
    first, then the site script. Page-specific JS should be pushed via
    @push('scripts') from the page — it renders right after this include.
--}}
{{-- Vendor libraries (place library files in public/assets/vendors/) --}}
{{-- <script src="{{ asset('assets/vendors/bootstrap/js/bootstrap.bundle.min.js') }}"></script> --}}

{{-- Site script --}}
<script src="{{ asset('assets/js/main.js') }}"></script>

{{-- Global navbar + footer behaviour (reused on every page) --}}
<script src="{{ asset('assets/js/frontend/navbar.js') }}" defer></script>
<script src="{{ asset('assets/js/frontend/footer.js') }}" defer></script>
