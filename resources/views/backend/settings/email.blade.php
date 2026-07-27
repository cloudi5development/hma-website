@extends('backend.template.layouts.template-base')

@section('title', 'Email / SMTP Settings')
@section('page_title', 'Settings')
@section('page_sub', 'Email / SMTP')

@php
    $s = fn ($k, $d = null) => \App\Models\Setting::get($k, $d);
    $hasPassword = filled($s('mail_password'));
    // Current encryption: stored value, else derived from the live config scheme.
    $enc = $s('mail_encryption', config('mail.mailers.smtp.scheme') === 'smtps' ? 'ssl' : 'tls');
@endphp

@section('content')

    <div class="page-head">
        <div>
            <h1 class="page-head__title">Settings</h1>
            <p class="page-head__sub">Configure how the site sends email. These override the .env values.</p>
        </div>
    </div>

    @include('backend.settings._nav')

    <div class="row g-3">
        <div class="col-12 col-lg-8">
            <form method="POST" action="{{ route('backend.settings.email.update') }}">
                @csrf @method('PUT')
                <div class="hm-card">
                    <div class="hm-card__head"><h2 class="hm-card__title">SMTP Configuration</h2></div>
                    <div class="hm-card__body">

                        <div class="form-row">
                            <label class="form-label" for="mail_mailer">Mailer</label>
                            <select id="mail_mailer" name="mail_mailer" class="form-control-hm" style="max-width:220px">
                                <option value="smtp" {{ $s('mail_mailer', config('mail.default')) === 'smtp' ? 'selected' : '' }}>SMTP (send real email)</option>
                                <option value="log"  {{ $s('mail_mailer', config('mail.default')) === 'log' ? 'selected' : '' }}>Log (write to log file, no send)</option>
                            </select>
                            <p class="form-hint">Use <strong>SMTP</strong> to actually deliver mail.</p>
                        </div>

                        <div class="row g-3">
                            <div class="col-12 col-md-8">
                                <div class="form-row">
                                    <label class="form-label" for="mail_host">SMTP Host</label>
                                    <input type="text" id="mail_host" name="mail_host" class="form-control-hm"
                                           value="{{ old('mail_host', $s('mail_host', config('mail.mailers.smtp.host'))) }}"
                                           placeholder="e.g. smtp.gmail.com / smtp-relay.brevo.com">
                                </div>
                            </div>
                            <div class="col-12 col-md-4">
                                <div class="form-row">
                                    <label class="form-label" for="mail_port">Port</label>
                                    <input type="number" id="mail_port" name="mail_port" class="form-control-hm"
                                           value="{{ old('mail_port', $s('mail_port', config('mail.mailers.smtp.port'))) }}"
                                           placeholder="465 or 587">
                                </div>
                            </div>
                        </div>

                        <div class="row g-3">
                            <div class="col-12 col-md-6">
                                <div class="form-row">
                                    <label class="form-label" for="mail_username">Username</label>
                                    <input type="text" id="mail_username" name="mail_username" class="form-control-hm"
                                           value="{{ old('mail_username', $s('mail_username', config('mail.mailers.smtp.username'))) }}"
                                           placeholder="e.g. you@example.com" autocomplete="off">
                                </div>
                            </div>
                            <div class="col-12 col-md-6">
                                <div class="form-row">
                                    <label class="form-label" for="mail_password">Password / SMTP key</label>
                                    <input type="password" id="mail_password" name="mail_password" class="form-control-hm"
                                           value="" placeholder="{{ $hasPassword ? '•••••••• (leave blank to keep current)' : 'Enter SMTP password' }}" autocomplete="new-password">
                                    <p class="form-hint">{{ $hasPassword ? 'A password is saved. Leave blank to keep it.' : 'For Gmail, use a 16-char App Password.' }}</p>
                                </div>
                            </div>
                        </div>

                        <div class="row g-3">
                            <div class="col-12 col-md-4">
                                <div class="form-row">
                                    <label class="form-label" for="mail_encryption">Encryption</label>
                                    <select id="mail_encryption" name="mail_encryption" class="form-control-hm">
                                        <option value="tls"  {{ $enc === 'tls' ? 'selected' : '' }}>TLS (port 587)</option>
                                        <option value="ssl"  {{ $enc === 'ssl' ? 'selected' : '' }}>SSL (port 465)</option>
                                        <option value="none" {{ $enc === 'none' ? 'selected' : '' }}>None</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-12 col-md-4">
                                <div class="form-row">
                                    <label class="form-label" for="mail_from_address">From Address</label>
                                    <input type="email" id="mail_from_address" name="mail_from_address" class="form-control-hm"
                                           value="{{ old('mail_from_address', $s('mail_from_address', config('mail.from.address'))) }}"
                                           placeholder="e.g. info@hiremindsacademy.com">
                                </div>
                            </div>
                            <div class="col-12 col-md-4">
                                <div class="form-row">
                                    <label class="form-label" for="mail_from_name">From Name</label>
                                    <input type="text" id="mail_from_name" name="mail_from_name" class="form-control-hm"
                                           value="{{ old('mail_from_name', $s('mail_from_name', config('mail.from.name'))) }}"
                                           placeholder="Hire Minds Academy">
                                </div>
                            </div>
                        </div>

                        <div class="mt-2">
                            <button type="submit" class="btn-brand">Save Email Settings</button>
                        </div>
                    </div>
                </div>
            </form>
        </div>

        <div class="col-12 col-lg-4">
            <div class="hm-card">
                <div class="hm-card__head"><h2 class="hm-card__title">Send a Test Email</h2></div>
                <div class="hm-card__body">
                    <p class="form-hint" style="margin-bottom:10px">Save your settings first, then send a test to confirm they work. Failures show the exact SMTP error.</p>
                    <form method="POST" action="{{ route('backend.settings.email.test') }}">
                        @csrf
                        <div class="form-row">
                            <label class="form-label" for="test_email">Send test to</label>
                            <input type="email" id="test_email" name="test_email" class="form-control-hm"
                                   value="{{ old('test_email', $s('mail_from_address', config('mail.from.address'))) }}" required>
                            @error('test_email') <p class="form-error">{{ $message }}</p> @enderror
                        </div>
                        <button type="submit" class="btn-brand" style="width:100%;justify-content:center">Send Test Email</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

@endsection
