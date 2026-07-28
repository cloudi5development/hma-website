@extends('backend.template.layouts.template-base')

@php $editing = $testimonial->exists; @endphp

@section('title', $editing ? 'Edit Testimonial' : 'Add Testimonial')
@section('page_title', $editing ? 'Edit Testimonial' : 'Add Testimonial')
@section('page_sub', 'Testimonials')

@section('content')

    <div class="page-head">
        <a href="{{ route('backend.testimonials.index') }}" class="btn-ghost">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"><path d="m15 18-6-6 6-6"/></svg>
            Back to Testimonials
        </a>
    </div>

    <form method="POST"
          action="{{ $editing ? route('backend.testimonials.update', $testimonial) : route('backend.testimonials.store') }}"
          enctype="multipart/form-data" novalidate>
        @csrf
        @if ($editing) @method('PUT') @endif

        {{-- One container for the whole form --}}
        <div class="hm-card mb-3">
            <div class="hm-card__body">

                {{-- Row 1 — name + active toggle --}}
                <div class="row g-3">
                    <div class="col-12 col-md-6">
                        <div class="form-row" style="margin-bottom:0">
                            <label class="form-label" for="name">Name</label>
                            <input type="text" id="name" name="name"
                                   class="form-control-hm @error('name') is-invalid @enderror"
                                   value="{{ old('name', $testimonial->name) }}" placeholder="e.g. Arjun Mehta" required>
                            @error('name') <p class="form-error">{{ $message }}</p> @enderror
                        </div>
                    </div>
                    <div class="col-12 col-md-6">
                        <div class="form-row" style="margin-bottom:0">
                            <label class="form-label">Status</label>
                            <div class="d-flex align-items-center" style="height:48px">
                                <label class="switch">
                                    <input type="hidden" name="is_active" value="0">
                                    <input type="checkbox" name="is_active" value="1" {{ old('is_active', $testimonial->is_active ?? true) ? 'checked' : '' }}>
                                    <span class="switch__track"></span>
                                    <span class="switch__label">Active</span>
                                </label>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Row 2 — role + company --}}
                <div class="row g-3 mt-2">
                    <div class="col-12 col-md-6">
                        <div class="form-row" style="margin-bottom:0">
                            <label class="form-label" for="role">Role</label>
                            <input type="text" id="role" name="role"
                                   class="form-control-hm @error('role') is-invalid @enderror"
                                   value="{{ old('role', $testimonial->role) }}" placeholder="e.g. Software Developer">
                            @error('role') <p class="form-error">{{ $message }}</p> @enderror
                        </div>
                    </div>
                    <div class="col-12 col-md-6">
                        <div class="form-row" style="margin-bottom:0">
                            <label class="form-label" for="company">Company <span class="form-hint" style="display:inline">(optional)</span></label>
                            <input type="text" id="company" name="company"
                                   class="form-control-hm @error('company') is-invalid @enderror"
                                   value="{{ old('company', $testimonial->company) }}" placeholder="e.g. Infosys">
                            @error('company') <p class="form-error">{{ $message }}</p> @enderror
                        </div>
                    </div>
                </div>

                {{-- Row 3 — review --}}
                <div class="form-row mt-4">
                    <label class="form-label" for="review">Review</label>
                    <textarea id="review" name="review" rows="4"
                              class="form-control-hm @error('review') is-invalid @enderror"
                              placeholder="What the learner said about their experience…" required>{{ old('review', $testimonial->review) }}</textarea>
                    @error('review') <p class="form-error">{{ $message }}</p> @enderror
                </div>

                {{-- Row 4 — photo --}}
                <div class="form-row">
                    <label class="form-label" for="photo">Photo</label>
                    <div class="d-flex align-items-center gap-3 flex-wrap">
                        <span class="tbl-logo tbl-logo--round" style="width:64px;height:64px">
                            <img id="photoPreview" src="{{ $editing ? $testimonial->photo_url : '' }}" alt=""
                                 style="{{ $editing ? '' : 'display:none' }}">
                        </span>
                        <div>
                            <input type="file" id="photo" name="photo" accept="image/*"
                                   class="form-control-hm @error('photo') is-invalid @enderror" style="height:auto;padding:9px 12px">
                            <p class="form-hint">WebP / PNG / JPG · max 2 MB · square headshot works best.</p>
                        </div>
                    </div>
                    @error('photo') <p class="form-error">{{ $message }}</p> @enderror
                </div>

                {{-- Row 5 — rating + display order --}}
                <div class="row g-3">
                    <div class="col-12 col-md-6">
                        <div class="form-row" style="margin-bottom:0">
                            <label class="form-label" for="rating">Rating</label>
                            <select id="rating" name="rating" class="form-control-hm @error('rating') is-invalid @enderror" style="max-width:220px">
                                @foreach ([5, 4, 3, 2, 1] as $r)
                                    <option value="{{ $r }}" {{ (int) old('rating', $testimonial->rating ?? 5) === $r ? 'selected' : '' }}>
                                        {{ str_repeat('★', $r) }} — {{ $r }} star{{ $r === 1 ? '' : 's' }}
                                    </option>
                                @endforeach
                            </select>
                            @error('rating') <p class="form-error">{{ $message }}</p> @enderror
                        </div>
                    </div>
                    <div class="col-12 col-md-6">
                        <div class="form-row" style="margin-bottom:0">
                            <label class="form-label" for="sort_order">Display Order</label>
                            <input type="number" id="sort_order" name="sort_order" min="0"
                                   class="form-control-hm @error('sort_order') is-invalid @enderror"
                                   value="{{ old('sort_order', $testimonial->sort_order ?? 0) }}" style="max-width:140px">
                            @error('sort_order') <p class="form-error">{{ $message }}</p> @enderror
                        </div>
                    </div>
                </div>

                {{-- Row 6 — page visibility --}}
                <div class="form-row mt-4" style="margin-bottom:0">
                    <label class="form-label">Page Visibility</label>
                    <div class="d-flex flex-wrap gap-2">
                        @foreach (['show_home' => 'Home', 'show_about' => 'About', 'show_testimonials' => 'Testimonials'] as $field => $label)
                            <label class="check-chip">
                                <input type="hidden" name="{{ $field }}" value="0">
                                <input type="checkbox" name="{{ $field }}" value="1" {{ old($field, $testimonial->$field ?? false) ? 'checked' : '' }}>
                                <span class="check-chip__box">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg>
                                </span>
                                <span class="check-chip__label">{{ $label }}</span>
                            </label>
                        @endforeach
                    </div>
                </div>

            </div>
        </div>

        <div class="d-flex gap-2">
            <button type="submit" class="btn-brand">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg>
                {{ $editing ? 'Save Changes' : 'Add Testimonial' }}
            </button>
            <a href="{{ route('backend.testimonials.index') }}" class="btn-ghost">Cancel</a>
        </div>
    </form>

@endsection

@push('scripts')
    <script>
        // Live photo preview
        var input = document.getElementById('photo'), img = document.getElementById('photoPreview');
        if (input) input.addEventListener('change', function () {
            if (this.files && this.files[0]) {
                img.src = URL.createObjectURL(this.files[0]);
                img.style.display = 'block';
            }
        });
    </script>
@endpush
