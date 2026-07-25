@extends('backend.template.layouts.template-base')

@php $editing = $story->exists; @endphp

@section('title', $editing ? 'Edit Success Story' : 'Add Success Story')
@section('page_title', $editing ? 'Edit Success Story' : 'Add Success Story')
@section('page_sub', 'Success Stories')

@section('content')

    <div class="page-head">
        <a href="{{ route('backend.success-stories.index') }}" class="btn-ghost">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"><path d="m15 18-6-6 6-6"/></svg>
            Back to Stories
        </a>
    </div>

    <form method="POST"
          action="{{ $editing ? route('backend.success-stories.update', $story) : route('backend.success-stories.store') }}"
          enctype="multipart/form-data" novalidate>
        @csrf
        @if ($editing) @method('PUT') @endif

        {{-- Success stories only appear on the home page, so visibility is fixed. --}}
        <input type="hidden" name="show_home" value="1">

        <div class="row g-3">
            <div class="col-12 col-lg-8">
                <div class="hm-card">
                    <div class="hm-card__body">

                        <div class="form-row">
                            <label class="form-label" for="name">Student Name</label>
                            <input type="text" id="name" name="name"
                                   class="form-control-hm @error('name') is-invalid @enderror"
                                   value="{{ old('name', $story->name) }}" placeholder="e.g. Aayushman Pravin" required>
                            @error('name') <p class="form-error">{{ $message }}</p> @enderror
                        </div>

                        <div class="row g-3">
                            <div class="col-12 col-md-6">
                                <div class="form-row">
                                    <label class="form-label" for="role">Role</label>
                                    <input type="text" id="role" name="role"
                                           class="form-control-hm @error('role') is-invalid @enderror"
                                           value="{{ old('role', $story->role) }}" placeholder="e.g. Software Developer">
                                    @error('role') <p class="form-error">{{ $message }}</p> @enderror
                                </div>
                            </div>
                            <div class="col-12 col-md-6">
                                <div class="form-row">
                                    <label class="form-label" for="salary">Package (LPA)</label>
                                    <input type="text" id="salary" name="salary"
                                           class="form-control-hm @error('salary') is-invalid @enderror"
                                           value="{{ old('salary', $story->salary) }}" placeholder="e.g. 9.0" required>
                                    <p class="form-hint">Just the number — shown as “₹9.0 LPA”.</p>
                                    @error('salary') <p class="form-error">{{ $message }}</p> @enderror
                                </div>
                            </div>
                        </div>

                        <div class="form-row">
                            <label class="form-label" for="image">Student Photo</label>
                            <div class="d-flex align-items-center gap-3 mb-2">
                                <span class="tbl-logo tbl-logo--round" style="width:64px;height:64px">
                                    <img id="imagePreview" src="{{ $editing ? $story->image_url : '' }}" alt=""
                                         style="{{ $editing ? '' : 'display:none' }}">
                                </span>
                                <div>
                                    <input type="file" id="image" name="image" accept="image/*"
                                           class="form-control-hm @error('image') is-invalid @enderror" style="height:auto;padding:9px 12px">
                                    <p class="form-hint">WebP / PNG / JPG · max 2 MB · a transparent cut-out of the student works best.</p>
                                </div>
                            </div>
                            @error('image') <p class="form-error">{{ $message }}</p> @enderror
                        </div>

                    </div>
                </div>
            </div>

            <div class="col-12 col-lg-4">
                <div class="hm-card mb-3">
                    <div class="hm-card__head"><h2 class="hm-card__title">Card Colour</h2></div>
                    <div class="hm-card__body">
                        <div class="form-row" style="margin-bottom:0">
                            <select name="tone" class="form-control-hm @error('tone') is-invalid @enderror" style="max-width:220px">
                                @foreach (\App\Models\SuccessStory::TONES as $tone)
                                    <option value="{{ $tone }}" {{ old('tone', $story->tone ?? 'olive') === $tone ? 'selected' : '' }}>
                                        {{ ucfirst($tone) }}
                                    </option>
                                @endforeach
                            </select>
                            @error('tone') <p class="form-error">{{ $message }}</p> @enderror
                            <p class="form-hint">The studio glow behind the student.</p>
                        </div>
                    </div>
                </div>

                <div class="hm-card">
                    <div class="hm-card__head"><h2 class="hm-card__title">Status</h2></div>
                    <div class="hm-card__body">
                        <label class="switch">
                            <input type="hidden" name="is_active" value="0">
                            <input type="checkbox" name="is_active" value="1" {{ old('is_active', $story->is_active ?? true) ? 'checked' : '' }}>
                            <span class="switch__track"></span>
                            <span class="switch__label">Active (visible on site)</span>
                        </label>
                        <div class="form-row mt-3" style="margin-bottom:0">
                            <label class="form-label" for="sort_order">Display Order</label>
                            <input type="number" id="sort_order" name="sort_order" min="0"
                                   class="form-control-hm @error('sort_order') is-invalid @enderror"
                                   value="{{ old('sort_order', $story->sort_order ?? 0) }}" style="max-width:140px">
                            @error('sort_order') <p class="form-error">{{ $message }}</p> @enderror
                            <p class="form-hint">Lower numbers show first.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="mt-3 d-flex gap-2">
            <button type="submit" class="btn-brand">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg>
                {{ $editing ? 'Save Changes' : 'Add Story' }}
            </button>
            <a href="{{ route('backend.success-stories.index') }}" class="btn-ghost">Cancel</a>
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
