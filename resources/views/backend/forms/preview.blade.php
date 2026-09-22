@extends('backend.template.layouts.template-base')

@section('title', 'Preview — ' . $form->name)
@section('page_title', 'Preview')
@section('page_sub', 'Forms')

@push('styles')
    {{-- The public form's own stylesheet, so what is previewed here looks like
         what a visitor gets rather than like an admin screen. --}}
    <link rel="stylesheet"
          href="{{ asset('assets/css/frontend/dynamic-form.css') }}?v={{ filemtime(public_path('assets/css/frontend/dynamic-form.css')) }}">
@endpush

@section('content')

    <div class="page-head">
        <a href="{{ route('backend.forms.edit', $form) }}" class="btn-ghost">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"><path d="m15 18-6-6 6-6"/></svg>
            Back to the builder
        </a>
        @if ($form->isPublished())
            <a href="{{ $form->public_url }}" target="_blank" rel="noopener" class="btn-brand">Open the live form</a>
        @endif
    </div>

    {{-- Said plainly and up front: this is a rehearsal. The form below runs the
         real validation the admin configured, and writes nothing. --}}
    <div class="hm-card mb-3">
        <div class="hm-card__body" style="display:flex;gap:12px;align-items:flex-start">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"
                 style="width:22px;height:22px;flex-shrink:0;color:#A6741F" aria-hidden="true">
                <circle cx="12" cy="12" r="9"/><path d="M12 8h.01M11 12h1v4h1"/>
            </svg>
            {{-- min-width:0 lets this flex item shrink below the hint's 500px
                 preferred width, so the note wraps on a phone instead of
                 pushing the page sideways. --}}
            <div style="flex:1;min-width:0">
                <p style="margin:0 0 4px;font-weight:600;color:var(--ink,#2E2620)">This is a preview.</p>
                <p class="form-hint" style="margin:0">
                    Try it exactly as a visitor would — the validation you configured runs for real.
                    <strong>Nothing you submit here is saved</strong>, so it will not appear in Responses.
                </p>
            </div>
        </div>
    </div>

    @if (session('success'))
        <div class="hm-card mb-3"><div class="hm-card__body" style="color:#1F7A4D">{{ session('success') }}</div></div>
    @endif
    @if (session('error'))
        <div class="hm-card mb-3"><div class="hm-card__body" style="color:#A6421F">{{ session('error') }}</div></div>
    @endif

    <div class="hm-card">
        <div class="hm-card__body" style="background:#FDF8F3">

            {{-- hmf--embed strips the public page's outer band, which would look
                 wrong nested inside an admin card. Everything else — the paper,
                 the controls, the spacing — is the visitor's. --}}
            <div class="hmf hmf--embed">
                <div class="hmf__paper">
                    {{-- The same banner partial the public page uses, so this
                         really is a preview of that page. --}}
                    @include('frontend.partials.form-header', ['form' => $form])

                    <div class="hmf__body @if ($form->hasSections()) hmf__body--grouped @endif">

                    @if ($form->fields->isEmpty())
                        <div class="hmf__closed">
                            <p>This form has no fields yet.
                               <a href="{{ route('backend.forms.edit', $form) }}">Add one</a> to see it here.</p>
                        </div>
                    @else
                        @if ($errors->any())
                            <div class="hmf__note hmf__note--warn" role="alert">
                                <p>The form reported {{ $errors->count() }} problem{{ $errors->count() === 1 ? '' : 's' }} — shown against the fields below.</p>
                            </div>
                        @endif

                        <form method="POST" action="{{ route('backend.forms.preview.submit', $form) }}"
                              enctype="multipart/form-data" class="hmf__form">
                            @csrf

                            {{-- The same renderer the public page uses, so a
                                 difference between preview and live is not
                                 possible by construction — the steps and
                                 section headings of a multi-page form included. --}}
                            @include('frontend.partials.form-structure', ['form' => $form])

                            <div class="hmf__actions" data-submit-row>
                                <button type="submit" class="hmf__submit">
                                    <span>{{ $form->submit_label }}</span>
                                </button>
                            </div>
                        </form>
                    @endif
                    </div>{{-- /.hmf__body --}}
                </div>

                @include('frontend.partials.form-footer')
            </div>

        </div>
    </div>

@endsection

@push('scripts')
    @include('frontend.partials.form-scripts')
@endpush
