@extends('backend.template.layouts.template-base')

@php $editing = $reel->exists; @endphp

@section('title', $editing ? 'Edit Reel' : 'Add Reel')
@section('page_title', $editing ? 'Edit Reel' : 'Add Reel')
@section('page_sub', 'Our Journey Reels')

@section('content')

    <div class="page-head">
        <a href="{{ route('backend.reels.index') }}" class="btn-ghost">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"><path d="m15 18-6-6 6-6"/></svg>
            Back to Reels
        </a>
    </div>

    <form method="POST"
          action="{{ $editing ? route('backend.reels.update', $reel) : route('backend.reels.store') }}"
          enctype="multipart/form-data" novalidate>
        @csrf
        @if ($editing) @method('PUT') @endif

        <div class="row g-3">
            <div class="col-12 col-lg-8">
                <div class="hm-card">
                    <div class="hm-card__body">

                        <div class="form-row">
                            <label class="form-label" for="title">Title</label>
                            <input type="text" id="title" name="title"
                                   class="form-control-hm @error('title') is-invalid @enderror"
                                   value="{{ old('title', $reel->title) }}" placeholder="e.g. Inside a Hire Minds Academy classroom" required>
                            <p class="form-hint">A short description of the reel (used for accessibility and the hover label).</p>
                            @error('title') <p class="form-error">{{ $message }}</p> @enderror
                        </div>

                        <div class="form-row">
                            <label class="form-label" for="video">Reel Video</label>
                            <div class="d-flex align-items-start gap-3 mb-2">
                                <span class="tbl-logo" style="width:64px;height:96px;background:#000">
                                    <video id="videoPreview" src="{{ $editing ? $reel->video_url : '' }}"
                                           muted playsinline
                                           style="width:100%;height:100%;object-fit:cover;{{ $editing ? '' : 'display:none' }}"></video>
                                </span>
                                <div>
                                    <input type="file" id="video" name="video" accept="video/*"
                                           class="form-control-hm @error('video') is-invalid @enderror" style="height:auto;padding:9px 12px">
                                    <p class="form-hint">MP4 / WebM / MOV · max 40 MB · a <strong>portrait (9:16)</strong> clip works best. It <strong>autoplays (muted) right in the card</strong> — no cover needed.</p>
                                </div>
                            </div>
                            @error('video') <p class="form-error">{{ $message }}</p> @enderror
                        </div>

                        <div class="form-row">
                            <label class="form-label" for="instagram_url">Instagram Link <span class="form-hint" style="display:inline">(optional)</span></label>
                            <input type="url" id="instagram_url" name="instagram_url"
                                   class="form-control-hm @error('instagram_url') is-invalid @enderror"
                                   value="{{ old('instagram_url', $reel->instagram_url) }}"
                                   placeholder="https://www.instagram.com/reel/XXXXXXXXX/">
                            <p class="form-hint">Optional — if set, clicking the card opens the full reel on Instagram.</p>
                            @error('instagram_url') <p class="form-error">{{ $message }}</p> @enderror
                        </div>

                    </div>
                </div>
            </div>

            <div class="col-12 col-lg-4">
                <div class="hm-card">
                    <div class="hm-card__head"><h2 class="hm-card__title">Status</h2></div>
                    <div class="hm-card__body">
                        <label class="switch">
                            <input type="hidden" name="is_active" value="0">
                            <input type="checkbox" name="is_active" value="1" {{ old('is_active', $reel->is_active ?? true) ? 'checked' : '' }}>
                            <span class="switch__track"></span>
                            <span class="switch__label">Active (visible on site)</span>
                        </label>
                        <div class="form-row mt-3" style="margin-bottom:0">
                            <label class="form-label" for="sort_order">Display Order</label>
                            <input type="number" id="sort_order" name="sort_order" min="0"
                                   class="form-control-hm @error('sort_order') is-invalid @enderror"
                                   value="{{ old('sort_order', $reel->sort_order ?? 0) }}" style="max-width:140px">
                            @error('sort_order') <p class="form-error">{{ $message }}</p> @enderror
                            <p class="form-hint">Lower numbers show first. Shows on both the home and testimonials pages.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="mt-3 d-flex gap-2">
            <button type="submit" class="btn-brand">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg>
                {{ $editing ? 'Save Changes' : 'Add Reel' }}
            </button>
            <a href="{{ route('backend.reels.index') }}" class="btn-ghost">Cancel</a>
        </div>
    </form>

@endsection

@push('scripts')
    <script>
        // Live video preview
        var input = document.getElementById('video'), vid = document.getElementById('videoPreview');
        if (input) input.addEventListener('change', function () {
            if (this.files && this.files[0]) {
                vid.src = URL.createObjectURL(this.files[0]);
                vid.style.display = 'block';
                vid.play().catch(function () {});
            }
        });
    </script>
@endpush
