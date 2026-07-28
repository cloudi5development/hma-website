@extends('backend.template.layouts.template-base')

@php $editing = $event->exists; @endphp

@section('title', $editing ? 'Edit Event' : 'Add Event')
@section('page_title', $editing ? 'Edit Event' : 'Add Event')
@section('page_sub', 'Upcoming Events')

@section('content')

    <div class="page-head">
        <a href="{{ route('backend.events.index') }}" class="btn-ghost">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"><path d="m15 18-6-6 6-6"/></svg>
            Back to Events
        </a>
    </div>

    <form method="POST"
          action="{{ $editing ? route('backend.events.update', $event) : route('backend.events.store') }}"
          enctype="multipart/form-data" novalidate>
        @csrf
        @if ($editing) @method('PUT') @endif

        {{-- Events only appear on the home page, so visibility is fixed to home. --}}
        <input type="hidden" name="show_home" value="1">

        {{-- One container for the whole form --}}
        <div class="hm-card mb-3">
            <div class="hm-card__body">

                {{-- Row 1 — speaker + active toggle --}}
                <div class="row g-3">
                    <div class="col-12 col-md-6">
                        <div class="form-row" style="margin-bottom:0">
                            <label class="form-label" for="speaker">Speaker</label>
                            <input type="text" id="speaker" name="speaker"
                                   class="form-control-hm @error('speaker') is-invalid @enderror"
                                   value="{{ old('speaker', $event->speaker) }}" placeholder="e.g. Rochelle Fernandez" required>
                            @error('speaker') <p class="form-error">{{ $message }}</p> @enderror
                        </div>
                    </div>
                    <div class="col-12 col-md-6">
                        <div class="form-row" style="margin-bottom:0">
                            <label class="form-label">Status</label>
                            <div class="d-flex align-items-center" style="height:48px">
                                <label class="switch">
                                    <input type="hidden" name="is_active" value="0">
                                    <input type="checkbox" name="is_active" value="1" {{ old('is_active', $event->is_active ?? true) ? 'checked' : '' }}>
                                    <span class="switch__track"></span>
                                    <span class="switch__label">Active</span>
                                </label>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Row 2 — title --}}
                <div class="form-row mt-2">
                    <label class="form-label" for="title">Event Title</label>
                    <input type="text" id="title" name="title"
                           class="form-control-hm @error('title') is-invalid @enderror"
                           value="{{ old('title', $event->title) }}" placeholder="e.g. Learn about no-code tools" required>
                    @error('title') <p class="form-error">{{ $message }}</p> @enderror
                </div>

                {{-- Row 3 — type + price --}}
                <div class="row g-3">
                    <div class="col-12 col-md-6">
                        <div class="form-row" style="margin-bottom:0">
                            <label class="form-label" for="type">Type</label>
                            <input type="text" id="type" name="type"
                                   class="form-control-hm @error('type') is-invalid @enderror"
                                   value="{{ old('type', $event->type ?? 'Live Event') }}" placeholder="e.g. Live Event">
                            @error('type') <p class="form-error">{{ $message }}</p> @enderror
                        </div>
                    </div>
                    <div class="col-12 col-md-6">
                        <div class="form-row" style="margin-bottom:0">
                            <label class="form-label" for="price">Price <span class="form-hint" style="display:inline">(optional)</span></label>
                            <input type="text" id="price" name="price"
                                   class="form-control-hm @error('price') is-invalid @enderror"
                                   value="{{ old('price', $event->price) }}" placeholder="e.g. ₹499/-">
                            @error('price') <p class="form-error">{{ $message }}</p> @enderror
                        </div>
                    </div>
                </div>

                {{-- Row 4 — link --}}
                <div class="form-row mt-4">
                    <label class="form-label" for="link">Event Details Link <span class="form-hint" style="display:inline">(optional)</span></label>
                    <input type="text" id="link" name="link"
                           class="form-control-hm @error('link') is-invalid @enderror"
                           value="{{ old('link', $event->link) }}" placeholder="https://… (leave blank for #)">
                    @error('link') <p class="form-error">{{ $message }}</p> @enderror
                </div>

                {{-- Row 5 — speaker photo --}}
                <div class="form-row">
                    <label class="form-label" for="image">Speaker Photo</label>
                    <div class="d-flex align-items-center gap-3 flex-wrap">
                        <span class="tbl-logo tbl-logo--round" style="width:64px;height:64px">
                            <img id="imagePreview" src="{{ $editing ? $event->image_url : '' }}" alt=""
                                 style="{{ $editing ? '' : 'display:none' }}">
                        </span>
                        <div>
                            <input type="file" id="image" name="image" accept="image/*"
                                   class="form-control-hm @error('image') is-invalid @enderror" style="height:auto;padding:9px 12px">
                            <p class="form-hint">WebP / PNG / JPG · max 2 MB · a transparent cut-out of the person works best.</p>
                        </div>
                    </div>
                    @error('image') <p class="form-error">{{ $message }}</p> @enderror
                </div>

                {{-- Row 6 — card colour + display order --}}
                <div class="row g-3">
                    <div class="col-12 col-md-6">
                        <div class="form-row" style="margin-bottom:0">
                            <label class="form-label" for="tone">Card Colour</label>
                            <select id="tone" name="tone" class="form-control-hm @error('tone') is-invalid @enderror" style="max-width:220px">
                                @foreach (\App\Models\Event::TONES as $tone)
                                    <option value="{{ $tone }}" {{ old('tone', $event->tone ?? 'purple') === $tone ? 'selected' : '' }}>
                                        {{ ucfirst($tone) }}
                                    </option>
                                @endforeach
                            </select>
                            @error('tone') <p class="form-error">{{ $message }}</p> @enderror
                            <p class="form-hint">The dark gradient behind the speaker.</p>
                        </div>
                    </div>
                    <div class="col-12 col-md-6">
                        <div class="form-row" style="margin-bottom:0">
                            <label class="form-label" for="sort_order">Display Order</label>
                            <input type="number" id="sort_order" name="sort_order" min="0"
                                   class="form-control-hm @error('sort_order') is-invalid @enderror"
                                   value="{{ old('sort_order', $event->sort_order ?? 0) }}" style="max-width:140px">
                            @error('sort_order') <p class="form-error">{{ $message }}</p> @enderror
                            <p class="form-hint">Lower numbers show first.</p>
                        </div>
                    </div>
                </div>

            </div>
        </div>

        <div class="d-flex gap-2">
            <button type="submit" class="btn-brand">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg>
                {{ $editing ? 'Save Changes' : 'Add Event' }}
            </button>
            <a href="{{ route('backend.events.index') }}" class="btn-ghost">Cancel</a>
        </div>
    </form>

@endsection

@push('scripts')
    <script>
        // Live photo preview
        var input = document.getElementById('image'), img = document.getElementById('imagePreview');
        if (input) input.addEventListener('change', function () {
            if (this.files && this.files[0]) {
                img.src = URL.createObjectURL(this.files[0]);
                img.style.display = 'block';
            }
        });
    </script>
@endpush
