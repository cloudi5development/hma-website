{{-- Flash messages now surface as toasts — see backend/partials/feedback.blade.php,
     included once by the admin layout. This partial is kept as a no-op so the
     many @include('backend.partials.flash') calls across the panel stay valid
     and no page shows the same message twice. --}}
