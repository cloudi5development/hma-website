{{--
    Global frontend scripts, loaded at the end of <body>. Vendor libraries
    first, then the site script. Page-specific JS should be pushed via
    @push('scripts') from the page — it renders right after this include.
--}}
{{-- assets/js/main.js was referenced here but has never existed — every page
     paid for a blocking request that 404'd. Removed rather than stubbed: there
     is nothing for it to do, and the navbar/footer scripts below already carry
     the global behaviour. --}}

{{-- Global navbar + footer behaviour (reused on every page) --}}
<script src="{{ asset('assets/js/frontend/navbar.js') }}" defer></script>
<script src="{{ asset('assets/js/frontend/footer.js') }}" defer></script>
