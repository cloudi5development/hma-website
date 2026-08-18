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

        {{-- One container for the whole form --}}
        <div class="hm-card mb-3">
            <div class="hm-card__body">

                {{-- Row 1 — title + active toggle --}}
                <div class="row g-3">
                    <div class="col-12 col-md-6">
                        <div class="form-row" style="margin-bottom:0">
                            <label class="form-label" for="title">Title</label>
                            <input type="text" id="title" name="title"
                                   class="form-control-hm @error('title') is-invalid @enderror"
                                   value="{{ old('title', $reel->title) }}" placeholder="e.g. Inside a Hire Minds Academy classroom" required>
                            <p class="form-hint">A short description of the reel (used for accessibility and the hover label).</p>
                            @error('title') <p class="form-error">{{ $message }}</p> @enderror
                        </div>
                    </div>
                    <div class="col-12 col-md-6">
                        <div class="form-row" style="margin-bottom:0">
                            <label class="form-label">Status</label>
                            <div class="d-flex align-items-center" style="height:48px">
                                <label class="switch">
                                    <input type="hidden" name="is_active" value="0">
                                    <input type="checkbox" name="is_active" value="1" {{ old('is_active', $reel->is_active ?? true) ? 'checked' : '' }}>
                                    <span class="switch__track"></span>
                                    <span class="switch__label">Active</span>
                                </label>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- The two ways to add a reel, said plainly before either field.
                     They do not behave the same on the site and an admin needs to
                     know that before choosing. --}}
                <div class="form-section">
                    <h2 class="form-section__title">How this reel plays</h2>
                </div>
                <div class="row g-3">
                    <div class="col-12 col-md-6">
                        <div class="reel-mode reel-mode--upload">
                            <p class="reel-mode__title">Upload a video file <span class="pill pill--tiny pill--active">Autoplays</span></p>
                            <p class="reel-mode__text">
                                Served from this site. The card <strong>starts playing on its own</strong>
                                (muted, looped) when it reaches the middle of the slider, with our own
                                play, sound and scrub controls.
                            </p>
                        </div>
                    </div>
                    <div class="col-12 col-md-6">
                        <div class="reel-mode reel-mode--link">
                            <p class="reel-mode__title">Paste an Instagram link <span class="pill pill--tiny">Click to play</span></p>
                            <p class="reel-mode__text">
                                Nothing to upload — Instagram's own player is shown. It
                                <strong>cannot autoplay</strong>: Instagram embeds are click-to-play by
                                their design, and the card uses their look rather than ours.
                            </p>
                        </div>
                    </div>
                </div>
                <p class="form-hint">
                    Fill in <strong>one</strong> of the two below. Do both and the uploaded file wins —
                    it plays on the card, and the link becomes the "Instagram Reel" badge on it.
                </p>

                {{-- Row 2 — video --}}
                <div class="form-row mt-4">
                    <label class="form-label" for="video">Reel Video</label>
                    <div class="d-flex align-items-start gap-3 flex-wrap">
                        <span class="tbl-logo" style="width:64px;height:96px;background:#000">
                            <video id="videoPreview" src="{{ $editing ? $reel->video_url : '' }}"
                                   muted playsinline
                                   style="width:100%;height:100%;object-fit:cover;{{ $editing ? '' : 'display:none' }}"></video>
                        </span>
                        <div>
                            <input type="file" id="video" name="video" accept="video/*"
                                   class="form-control-hm @error('video') is-invalid @enderror" style="height:auto;padding:9px 12px">
                            @php
                                $maxKb   = \App\Support\UploadLimit::cap(\App\Http\Requests\Backend\ReelRequest::PREFERRED_MAX_KB);
                                $capped  = \App\Support\UploadLimit::isServerCapped(\App\Http\Requests\Backend\ReelRequest::PREFERRED_MAX_KB);
                            @endphp
                            <p class="form-hint">
                                <strong>1080 × 1920 px</strong> (portrait 9:16) ·
                                MP4 / WebM / MOV · max {{ \App\Support\UploadLimit::label($maxKb) }}.
                                It <strong>autoplays (muted) right in the card</strong> — no cover needed.
                            </p>
                            @if ($capped)
                                {{-- The server, not the app, is what caps this — say so up front
                                     rather than letting the upload fail after the wait. --}}
                                <p class="form-hint" style="color:#A6741F">
                                    This server currently accepts uploads up to
                                    <strong>{{ \App\Support\UploadLimit::label($maxKb) }}</strong>
                                    ({{ ini_get('upload_max_filesize') }} upload_max_filesize /
                                    {{ ini_get('post_max_size') }} post_max_size).
                                    Ask your host to raise both to 40M for full-length reels.
                                </p>
                            @endif
                        </div>
                    </div>
                    @error('video') <p class="form-error">{{ $message }}</p> @enderror
                </div>

                {{-- Row 3 — instagram link + display order --}}
                <div class="row g-3">
                    <div class="col-12 col-md-6">
                        <div class="form-row" style="margin-bottom:0">
                            <label class="form-label" for="instagram_url">Instagram Link</label>
                            <input type="url" id="instagram_url" name="instagram_url"
                                   class="form-control-hm @error('instagram_url') is-invalid @enderror"
                                   value="{{ old('instagram_url', $reel->instagram_url) }}"
                                   placeholder="https://www.instagram.com/reel/XXXXXXXXX/">
                            <p class="form-hint">
                                With <strong>no video uploaded</strong>, the reel is played from here —
                                paste the link and it appears on the site (click-to-play).
                                Alongside an upload, it just adds the "Instagram Reel" badge to the card.
                            </p>
                            @error('instagram_url') <p class="form-error">{{ $message }}</p> @enderror
                        </div>
                    </div>
                    <div class="col-12 col-md-6">
                        <div class="form-row" style="margin-bottom:0">
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
        <div class="d-flex gap-2">
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

@push('styles')
    <style>
        .reel-mode {
            height: 100%;
            padding: 14px 16px;
            border: 1px solid var(--line, #E7DED2);
            border-left: 3px solid var(--line, #E7DED2);
            border-radius: 10px;
        }
        .reel-mode--upload { border-left-color: #1F7A4D; }
        .reel-mode--link   { border-left-color: #E1306C; }
        .reel-mode__title  { margin: 0 0 6px; font-size: 13.5px; font-weight: 700; color: var(--ink, #2E2620); }
        .reel-mode__text   { margin: 0; font-size: 12.5px; line-height: 1.7; color: var(--muted, #8A7E70); }
    </style>
@endpush
