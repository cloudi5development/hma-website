{{--
| A Register trigger. Opens the registration modal, unless the event carries its
| own registration link — then it is an ordinary link to that address and the
| modal is never rendered.
|
| Params: $registerUrl (string|null), $class, $label, $arrow (bool).
--}}
@php
    $classes = $class ?? 'hm-ed-btn hm-ed-btn--primary';
    $label   = $label ?? 'Register Now';
@endphp

@if ($registerUrl)
    <a class="{{ $classes }}" href="{{ $registerUrl }}">
        {{ $label }}
        @if ($arrow ?? false)
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <path d="M7 17 17 7M8.5 7H17v8.5"/>
            </svg>
        @endif
    </a>
@else
    <button class="{{ $classes }}" type="button" data-bs-toggle="modal" data-bs-target="#hmRegisterModal">
        {{ $label }}
        @if ($arrow ?? false)
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <path d="M7 17 17 7M8.5 7H17v8.5"/>
            </svg>
        @endif
    </button>
@endif
