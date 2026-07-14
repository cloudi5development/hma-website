{{-- Base document meta: charset, viewport, CSRF token and per-page overridable SEO meta. --}}
<meta charset="utf-8">
<meta http-equiv="X-UA-Compatible" content="IE=edge">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="csrf-token" content="{{ csrf_token() }}">

<meta name="description" content="@yield('meta_description', config('app.name'))">
<meta name="keywords" content="@yield('meta_keywords', '')">
<meta name="author" content="{{ config('app.name') }}">
<meta name="robots" content="@yield('meta_robots', 'index, follow')">

<link rel="canonical" href="{{ url()->current() }}">
<link rel="icon" type="image/png" href="{{ asset('assets/images/favicon.png') }}">
