{{-- Admin panel document meta: charset, viewport, CSRF token, favicon. Not indexed by search engines. --}}
<meta charset="utf-8">
<meta http-equiv="X-UA-Compatible" content="IE=edge">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="csrf-token" content="{{ csrf_token() }}">
<meta name="robots" content="noindex, nofollow">

<link rel="icon" type="image/png" href="{{ asset('backend/template/images/favicon.png') }}">
