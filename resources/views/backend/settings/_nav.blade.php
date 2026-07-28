{{-- Settings sub-navigation — shared by every settings page. --}}
@php
    $settingsTabs = [
        'general' => 'General',
        'logo'    => 'Logo',
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

