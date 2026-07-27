{{-- Settings sub-navigation — shared by every settings page. --}}
@php
    $settingsTabs = [
        'general' => 'General',
        'contact' => 'Contact',
        'social'  => 'Social Media',
        'email'   => 'Email / SMTP',
        'seo'     => 'SEO Defaults',
    ];
@endphp
<div class="hm-card mb-3">
    <div class="hm-card__body d-flex flex-wrap gap-2">
        @foreach ($settingsTabs as $key => $label)
            <a href="{{ route('backend.settings.' . $key) }}"
               class="{{ request()->routeIs('backend.settings.' . $key) ? 'btn-brand' : 'btn-ghost' }}">{{ $label }}</a>
        @endforeach
    </div>
</div>

@if (session('success'))
    <div class="alert-hm alert-hm--success">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg>
        {{ session('success') }}
    </div>
@endif
@if (session('error'))
    <div class="alert-hm alert-hm--danger" style="background:#fdecea;color:#a3271f;border-color:#f5c6c2;">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 8v5M12 16h.01M10.3 3.9 1.8 18a2 2 0 0 0 1.7 3h17a2 2 0 0 0 1.7-3L13.7 3.9a2 2 0 0 0-3.4 0Z"/></svg>
        {{ session('error') }}
    </div>
@endif
