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

        <div class="row g-3">
            {{-- Left column copy + buttons --}}
            <div class="col-12 col-lg-7">
                <div class="hm-card">
                    <div class="hm-card__head"><h2 class="hm-card__title">Left Column</h2></div>
                    <div class="hm-card__body">

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
                </div>
            </div>

            {{-- Right column image --}}
            <div class="col-12 col-lg-5">
                <div class="hm-card">
                    <div class="hm-card__head"><h2 class="hm-card__title">Right Column Image</h2></div>
                    <div class="hm-card__body">

                        @if ($hero->image)
                            <div class="hm-media mb-3"><img id="heroPreview" src="{{ $hero->image_url }}" alt="Hero image"></div>
                        @else
                            <div class="hm-media hm-media--empty mb-3" id="heroPreviewWrap">
                                <img id="heroPreview" src="{{ asset('backend/template/images/actions/product-img.svg') }}" alt="">
                                No image uploaded
                            </div>
                        @endif

                        <div class="form-row" style="margin-bottom:0">
                            <label class="form-label" for="image">Upload New Image</label>
                            <input type="file" id="image" name="image" accept="image/*"
                                   class="form-control-hm @error('image') is-invalid @enderror" style="height:auto;padding:9px 12px">
                            <p class="form-hint">WebP / PNG / JPG · max 3 MB · around 560 × 548 px. Leave empty to keep the current image.</p>
                            @error('image') <p class="form-error">{{ $message }}</p> @enderror
                        </div>

                    </div>
                </div>
            </div>
        </div>

        <div class="mt-3 d-flex gap-2">
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
        // Live hero image preview
        var input = document.getElementById('image'), img = document.getElementById('heroPreview');
        if (input) input.addEventListener('change', function () {
            if (this.files && this.files[0]) {
                var wrap = document.getElementById('heroPreviewWrap');
                if (wrap) wrap.classList.remove('hm-media--empty');
                img.src = URL.createObjectURL(this.files[0]);
            }
        });
    </script>
@endpush
