{{--
    Admin panel stylesheets — completely separate from the frontend assets.
    Page-specific CSS → @push('styles').
--}}
{{-- Inter (body) + Poppins (headings & numbers) --}}
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Poppins:wght@500;600;700&display=swap">

{{-- Bootstrap 5 — grid + utilities only; the look is admin.css --}}
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" crossorigin="anonymous">

{{-- Admin theme. ?v=<mtime> so an edit is never masked by a cached copy. --}}
<link rel="stylesheet"
      href="{{ asset('backend/assets/css/admin.css') }}?v={{ filemtime(public_path('backend/assets/css/admin.css')) }}">
