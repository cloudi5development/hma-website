@extends('backend.template.layouts.template-base')

@section('title', 'reCAPTCHA Settings')
@section('page_title', 'Settings')
@section('page_sub', 'reCAPTCHA')

@php
    $s            = fn ($k, $d = null) => \App\Models\Setting::get($k, $d);
    $hasSecret    = filled($s('recaptcha_secret_key'));
    $verifier     = app(\App\Services\RecaptchaVerifier::class);
    $isOn         = $verifier->enabled();
    // Only ever the site key: it is public by design (the page carries it).
    // The secret is never rendered back into the form.
    $siteKey      = $s('recaptcha_site_key');
    $fromEnv      = blank($siteKey) && filled(config('services.recaptcha.site_key'));
@endphp

@section('content')

    <div class="page-head">
        <div>
            <h1 class="page-head__title">Settings</h1>
            <p class="page-head__sub">The keys that put the “I’m not a robot” box on every form.</p>
        </div>
    </div>

    @include('backend.settings._nav')

    <div class="row g-3">
        <div class="col-12 col-lg-8">
            <form method="POST" action="{{ route('backend.settings.recaptcha.update') }}">
                @csrf @method('PUT')
                <div class="hm-card">
                    <div class="hm-card__head"><h2 class="hm-card__title">Google reCAPTCHA</h2></div>
                    <div class="hm-card__body">

                        <div class="form-row">
                            <label class="form-label" for="recaptcha_site_key">Site Key</label>
                            <input type="text" id="recaptcha_site_key" name="recaptcha_site_key"
                                   class="form-control-hm @error('recaptcha_site_key') is-invalid @enderror"
                                   value="{{ old('recaptcha_site_key', $siteKey) }}"
                                   placeholder="6Lxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx"
                                   autocomplete="off" spellcheck="false">
                            @error('recaptcha_site_key') <p class="form-error">{{ $message }}</p> @enderror
                            <p class="form-hint">The key the page shows to visitors. Paste it with the console’s <strong>COPY SITE KEY</strong> button — retyping it is how an <em>l</em> becomes an <em>I</em>.</p>
                        </div>

                        <div class="form-row">
                            <label class="form-label" for="recaptcha_secret_key">Secret Key</label>
                            <input type="password" id="recaptcha_secret_key" name="recaptcha_secret_key"
                                   class="form-control-hm @error('recaptcha_secret_key') is-invalid @enderror"
                                   value=""
                                   placeholder="{{ $hasSecret ? '•••••••• (leave blank to keep current)' : '6Lxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx' }}"
                                   autocomplete="off" spellcheck="false">
                            @error('recaptcha_secret_key') <p class="form-error">{{ $message }}</p> @enderror
                            <p class="form-hint">
                                Used only between this server and Google — it is never shown on the website.
                                @if ($hasSecret) A secret is saved. Leave this blank to keep it. @endif
                            </p>
                        </div>

                        <div class="mt-2">
                            <button type="submit" class="btn-brand">Save reCAPTCHA Keys</button>
                        </div>
                    </div>
                </div>
            </form>
        </div>

        <div class="col-12 col-lg-4">
            <div class="hm-card">
                <div class="hm-card__head"><h2 class="hm-card__title">Status</h2></div>
                <div class="hm-card__body">

                    @if ($isOn)
                        <p style="margin:0 0 10px;font-weight:600;color:#1F7A4D">
                            On — the box appears above the submit button on every form.
                        </p>
                    @else
                        <p style="margin:0 0 10px;font-weight:600;color:#A6421F">
                            Off — both keys are needed. The forms work exactly as they did before.
                        </p>
                    @endif

                    <p class="form-hint" style="margin:0 0 12px">
                        Covers the contact form, the course enquiry, the event registration
                        and every form built under <strong>Forms</strong>.
                    </p>

                    @if ($fromEnv)
                        <p class="form-hint" style="margin:0 0 12px">
                            Currently reading the key from the server’s <code>.env</code>.
                            Anything typed here replaces it.
                        </p>
                    @endif

                    {{-- The single most common cause of "it works locally but not
                         on the live site", so it is said on the screen. --}}
                    <p class="form-hint" style="margin:0 0 12px">
                        Make a <strong>v2 “I’m not a robot” Checkbox</strong> key, and list
                        every domain the site runs on — a domain that is missing shows
                        <em>“ERROR for site owner”</em> in place of the box.
                    </p>

                    <a href="https://www.google.com/recaptcha/admin" target="_blank" rel="noopener" class="btn-ghost">
                        Open the reCAPTCHA console
                    </a>
                </div>
            </div>
        </div>
    </div>

@endsection
