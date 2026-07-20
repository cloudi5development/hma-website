{{--
    Global frontend stylesheets. Load vendor libraries first, then the site
    stylesheet last so it can override them. Page-specific CSS should be pushed
    via @push('styles') from the page, not added here.
--}}
{{-- Page loader — first, and before the fonts, so the overlay is styled the
     moment <body> parses and the page never flashes behind it. --}}
<link rel="stylesheet" href="{{ asset('assets/css/frontend/loader.css') }}?v={{ filemtime(public_path('assets/css/frontend/loader.css')) }}">

{{-- Geist — site-wide typeface.
     The range is 400..900, not 100..900: nothing on the site uses a weight
     below 400, and a narrower variable range is a smaller download.
     display=swap keeps text painting in the fallback instead of blocking FCP.
     preconnect warms both hosts; the stylesheet on fonts.googleapis.com is
     render-blocking, so the DNS/TLS cost is paid up front rather than serially. --}}
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Geist:wght@400..900&display=swap">

{{-- Vendor libraries (place library files in public/assets/vendors/) --}}
{{-- <link rel="stylesheet" href="{{ asset('assets/vendors/bootstrap/css/bootstrap.min.css') }}"> --}}

{{-- Bootstrap 5 — site-wide (grid used by the hero and the footer) --}}
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" crossorigin="anonymous">

{{-- Icon font — site-wide (needed by the global navbar and other pages) --}}
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" crossorigin="anonymous" referrerpolicy="no-referrer">

{{-- Site stylesheet (loaded after Bootstrap so its resets/Geist body font win) --}}
<link rel="stylesheet" href="{{ asset('assets/css/style.css') }}?v={{ filemtime(public_path('assets/css/style.css')) }}">

{{-- Global navbar + footer (markup in layouts/, reused on every page) --}}
<link rel="stylesheet" href="{{ asset('assets/css/frontend/navbar.css') }}?v={{ filemtime(public_path('assets/css/frontend/navbar.css')) }}">
<link rel="stylesheet" href="{{ asset('assets/css/frontend/footer.css') }}?v={{ filemtime(public_path('assets/css/frontend/footer.css')) }}">
