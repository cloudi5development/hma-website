@extends('backend.template.layouts.template-base')

@section('title', 'Edit Hero Section')
@section('page_title', 'Edit Hero Section')
@section('page_sub', 'Hero Section')

@section('content')

    <div class="page-head">
        <a href="{{ route('backend.hero.index') }}" class="btn-ghost">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"><path d="m15 18-6-6 6-6"/></svg>
            Back to Hero
        </a>
    </div>

    <form method="POST" action="{{ route('backend.hero.update') }}" enctype="multipart/form-data" novalidate>
        @csrf
        @method('PUT')

        {{-- One container for the whole form; the two columns are headed inside it. --}}
        <div class="hm-card mb-3">
            <div class="hm-card__body">
                <div class="row g-4">
                    {{-- Left column copy + buttons --}}
                    <div class="col-12 col-lg-7">
                        <h3 class="form-section__title mb-3">Left Column</h3>

                        <div class="form-row">
                            <label class="form-label" for="badge_text">Badge Text</label>
                            <input type="text" id="badge_text" name="badge_text"
                                   class="form-control-hm @error('badge_text') is-invalid @enderror"
                                   value="{{ old('badge_text', $hero->badge_text) }}" placeholder="e.g. Learn • Practice • Get Hired" required>
                            <p class="form-hint">Use the bullet character “•” between words to match the design.</p>
                            @error('badge_text') <p class="form-error">{{ $message }}</p> @enderror
                        </div>

                        <div class="form-row">
                            <label class="form-label" for="title">Main Title</label>
                            <input type="text" id="title" name="title"
                                   class="form-control-hm @error('title') is-invalid @enderror"
                                   value="{{ old('title', $hero->title) }}" placeholder="e.g. Your Future Starts With the Right Skills" required>
                            @error('title') <p class="form-error">{{ $message }}</p> @enderror
                        </div>

                        <div class="form-row">
                            <label class="form-label" for="description">Description</label>
                            <textarea id="description" name="description" rows="4"
                                      class="form-control-hm @error('description') is-invalid @enderror"
                                      placeholder="A short supporting sentence…" required>{{ old('description', $hero->description) }}</textarea>
                            @error('description') <p class="form-error">{{ $message }}</p> @enderror
                        </div>

                        <div class="row g-3">
                            <div class="col-12 col-md-6">
                                <div class="form-row">
                                    <label class="form-label" for="btn1_text">Primary Button — Text</label>
                                    <input type="text" id="btn1_text" name="btn1_text"
                                           class="form-control-hm @error('btn1_text') is-invalid @enderror"
                                           value="{{ old('btn1_text', $hero->btn1_text) }}" placeholder="e.g. Explore Course" required>
                                    @error('btn1_text') <p class="form-error">{{ $message }}</p> @enderror
                                </div>
                            </div>
                            <div class="col-12 col-md-6">
                                <div class="form-row">
                                    <label class="form-label" for="btn1_url">Primary Button — Link</label>
                                    <input type="text" id="btn1_url" name="btn1_url"
                                           class="form-control-hm @error('btn1_url') is-invalid @enderror"
                                           value="{{ old('btn1_url', $hero->btn1_url) }}" placeholder="#courses or a full URL">
                                    @error('btn1_url') <p class="form-error">{{ $message }}</p> @enderror
                                </div>
                            </div>
                        </div>

                        <div class="row g-3">
                            <div class="col-12 col-md-6">
                                <div class="form-row" style="margin-bottom:0">
                                    <label class="form-label" for="btn2_text">Secondary Button — Text</label>
                                    <input type="text" id="btn2_text" name="btn2_text"
                                           class="form-control-hm @error('btn2_text') is-invalid @enderror"
                                           value="{{ old('btn2_text', $hero->btn2_text) }}" placeholder="e.g. Apply" required>
                                    @error('btn2_text') <p class="form-error">{{ $message }}</p> @enderror
                                </div>
                            </div>
                            <div class="col-12 col-md-6">
                                <div class="form-row" style="margin-bottom:0">
                                    <label class="form-label" for="btn2_url">Secondary Button — Link</label>
                                    <input type="text" id="btn2_url" name="btn2_url"
                                           class="form-control-hm @error('btn2_url') is-invalid @enderror"
                                           value="{{ old('btn2_url', $hero->btn2_url) }}" placeholder="Leave blank to link to Contact">
                                    @error('btn2_url') <p class="form-error">{{ $message }}</p> @enderror
                                </div>
                            </div>
                        </div>

                    </div>

                    {{-- Right column photo. The yellow shape behind it is part of
                         the theme and is not editable — this is only the cut-out
                         that sits on top of it. --}}
                    <div class="col-12 col-lg-5">
                        <h3 class="form-section__title mb-3">Right Column Photo</h3>

                        {{-- Previewed on the real backdrop, so what the panel shows
                             is what the home page will draw. --}}
                        <div class="hero-shot mb-3">
                            <img class="hero-shot__bg" src="{{ asset(\App\Models\Hero::BACKDROP) }}" alt="" aria-hidden="true">
                            <img class="hero-shot__person" id="heroPreview"
                                 src="{{ $hero->image_url }}"
                                 alt="Hero photo" style="{{ $hero->image_url ? '' : 'display:none' }}">
                        </div>

                        <div class="form-row">
                            <label class="form-label" for="image">Upload New Photo</label>
                            <input type="file" id="image" name="image" accept="image/*"
                                   class="form-control-hm @error('image') is-invalid @enderror" style="height:auto;padding:9px 12px">
                            <p class="form-hint">
                                <strong>1000 × 1150 px</strong> (portrait) · WebP / PNG / JPG · max 3 MB.
                                A <strong>cut-out with a transparent background</strong> works best — it
                                sits on the yellow shape rather than in a box. Leave empty to keep the
                                current photo.
                            </p>
                            @error('image') <p class="form-error">{{ $message }}</p> @enderror
                        </div>

                        @if ($hero->image)
                            <label class="d-inline-flex align-items-center gap-2 form-hint" style="cursor:pointer">
                                <input type="checkbox" name="remove_image" value="1">
                                Remove the photo — leave just the yellow shape
                            </label>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <div class="d-flex gap-2">
            <button type="submit" class="btn-brand">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg>
                Save Changes
            </button>
            <a href="{{ route('backend.hero.index') }}" class="btn-ghost">Cancel</a>
        </div>
    </form>

@endsection

@push('scripts')
    <script>
        (function () {
            'use strict';

            var input  = document.getElementById('image'),
                img    = document.getElementById('heroPreview'),
                remove = document.querySelector('[name="remove_image"]');

            // Live preview, drawn straight onto the backdrop.
            if (input) input.addEventListener('change', function () {
                if (this.files && this.files[0]) {
                    img.src = URL.createObjectURL(this.files[0]);
                    img.style.display = '';
                    if (remove) remove.checked = false;   // a new photo overrides a pending removal
                }
            });

            // Ticking "remove" shows what the hero will actually look like.
            if (remove) remove.addEventListener('change', function () {
                img.style.display = this.checked ? 'none' : '';
                if (this.checked && input) input.value = '';
            });
        })();
    </script>
@endpush
