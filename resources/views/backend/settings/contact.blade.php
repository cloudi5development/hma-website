@extends('backend.template.layouts.template-base')

@section('title', 'Contact Settings')
@section('page_title', 'Settings')
@section('page_sub', 'Contact')

@php $s = fn ($k, $d = null) => \App\Models\Setting::get($k, $d); @endphp

@section('content')

    <div class="page-head">
        <div><h1 class="page-head__title">Settings</h1><p class="page-head__sub">Contact details shown across the site</p></div>
    </div>

    @include('backend.settings._nav')

    <form method="POST" action="{{ route('backend.settings.contact.update') }}">
        @csrf @method('PUT')
        <div class="hm-card">
            <div class="hm-card__body" style="max-width:760px">
                <div class="row g-3">
                    <div class="col-12 col-md-6">
                        <div class="form-row">
                            <label class="form-label" for="contact_phone">Phone</label>
                            <input type="text" id="contact_phone" name="contact_phone" class="form-control-hm"
                                   value="{{ old('contact_phone', $s('contact_phone', '+91 78240 94044')) }}">
                        </div>
                    </div>
                    <div class="col-12 col-md-6">
                        <div class="form-row">
                            <label class="form-label" for="contact_email">Email</label>
                            <input type="email" id="contact_email" name="contact_email" class="form-control-hm @error('contact_email') is-invalid @enderror"
                                   value="{{ old('contact_email', $s('contact_email', 'info@hiremindsacademy.com')) }}">
                            @error('contact_email') <p class="form-error">{{ $message }}</p> @enderror
                        </div>
                    </div>
                </div>
                <div class="form-row">
                    <label class="form-label" for="contact_address_chennai">Chennai Branch Address</label>
                    <textarea id="contact_address_chennai" name="contact_address_chennai" rows="2" class="form-control-hm">{{ old('contact_address_chennai', $s('contact_address_chennai', 'No 22 / 97, KGEYES VEDA RANGA NIVAS 4th Floor, 4th Avenue, Ashok Nagar, Chennai – 33')) }}</textarea>
                </div>
                <div class="form-row" style="margin-bottom:0">
                    <label class="form-label" for="contact_address_coimbatore">Coimbatore Branch Address</label>
                    <textarea id="contact_address_coimbatore" name="contact_address_coimbatore" rows="2" class="form-control-hm">{{ old('contact_address_coimbatore', $s('contact_address_coimbatore', '339, Chinnasamy Naidu Rd, Siddhapudur, Balasundaram Layout, B.K.R Nagar, Coimbatore, Tamil Nadu 641044')) }}</textarea>
                </div>
                <div class="mt-3"><button type="submit" class="btn-brand">Save Contact Settings</button></div>
            </div>
        </div>
    </form>

@endsection
