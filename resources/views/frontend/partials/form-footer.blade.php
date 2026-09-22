{{--
| The small print under a dynamic form.
|
| A form page stands on its own — no site header, no site footer — so the few
| things a form still owes the person filling it in are printed here instead:
| the one safety line worth putting on anything that collects answers, a way to
| reach a person about the form itself, and who it belongs to. The shape is the
| one every form service uses under the card.
|
| The only link is an email address: nothing here may lead into the rest of the
| website (see frontend/form.blade.php).
|
| Shared by the public page and the admin preview, so the preview shows what a
| visitor sees. Styled by assets/css/frontend/dynamic-form.css.
--}}
@php
    // The same source the site footer reads, so the address here is the one
    // Settings -> Contact shows, with the same fallback when it is empty.
    $formContact = \App\Models\Setting::contactDetails();
@endphp

<footer class="hmf__foot">
    <p class="hmf__foot-note">Never submit passwords or card details through this form.</p>

    @if ($formContact['email'])
        <p class="hmf__foot-help">
            Something wrong with this form?
            <a href="{{ $formContact['email_href'] }}">{{ $formContact['email'] }}</a>
        </p>
    @endif

    <p class="hmf__foot-brand">
        &copy;{{ date('Y') }} {{ \App\Models\Setting::get('copyright_text', \App\Models\Setting::get('site_name', config('app.name')) . '. All Rights Reserved.') }}
    </p>
</footer>
