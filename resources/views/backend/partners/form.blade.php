@extends('backend.template.layouts.template-base')

@php $editing = $partner->exists; @endphp

@section('title', $editing ? 'Edit Partner' : 'Add Partner')
@section('page_title', $editing ? 'Edit Partner' : 'Add Partner')
@section('page_sub', 'Trusted Partners')

@section('content')

    <div class="page-head">
        <a href="{{ route('backend.partners.index') }}" class="btn-ghost">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"><path d="m15 18-6-6 6-6"/></svg>
            Back to Partners
        </a>
    </div>

    <form method="POST"
          action="{{ $editing ? route('backend.partners.update', $partner) : route('backend.partners.store') }}"
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
                            <label class="form-label" for="name">Partner Name</label>
                            <input type="text" id="name" name="name"
                                   class="form-control-hm @error('name') is-invalid @enderror"
                                   value="{{ old('name', $partner->name) }}" placeholder="e.g. Amazon" required>
                            @error('name') <p class="form-error">{{ $message }}</p> @enderror
                        </div>
                    </div>
                    <div class="col-12 col-md-6">
                        <div class="form-row" style="margin-bottom:0">
                            <label class="form-label">Status</label>
                            <div class="d-flex align-items-center" style="height:48px">
                                <label class="switch">
                                    <input type="hidden" name="is_active" value="0">
                                    <input type="checkbox" name="is_active" value="1" {{ old('is_active', $partner->is_active ?? true) ? 'checked' : '' }}>
                                    <span class="switch__track"></span>
                                    <span class="switch__label">Active</span>
                                </label>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Row 2 — logo --}}
                <div class="form-row mt-4">
                    <label class="form-label" for="logo">Partner Logo</label>
                    <div class="d-flex align-items-center gap-3 flex-wrap">
                        <span class="tbl-logo" style="width:96px;height:60px">
                            <img id="logoPreview" src="{{ $editing ? $partner->logo_url : '' }}" alt=""
                                 style="{{ $editing ? '' : 'display:none' }}">
                        </span>
                        <div>
                            <input type="file" id="logo" name="logo" accept="image/*"
                                   class="form-control-hm @error('logo') is-invalid @enderror" style="height:auto;padding:9px 12px">
                            <p class="form-hint">WebP / PNG / JPG / SVG · max 2 MB · transparent background recommended.</p>
                        </div>
                    </div>
                    @error('logo') <p class="form-error">{{ $message }}</p> @enderror
                </div>

                {{-- Row 3 — display order --}}
                <div class="form-row">
                    <label class="form-label" for="sort_order">Display Order</label>
                    <input type="number" id="sort_order" name="sort_order" min="0"
                           class="form-control-hm @error('sort_order') is-invalid @enderror"
                           value="{{ old('sort_order', $partner->sort_order ?? 0) }}" style="max-width:160px">
                    <p class="form-hint">Lower numbers appear first in the marquee.</p>
                    @error('sort_order') <p class="form-error">{{ $message }}</p> @enderror
                </div>

                {{-- Row 4 — page visibility --}}
                <div class="form-row" style="margin-bottom:0">
                    <label class="form-label">Page Visibility</label>
                    <div class="d-flex flex-wrap gap-2">
                        @foreach (['show_home' => 'Home', 'show_about' => 'About', 'show_testimonials' => 'Testimonials'] as $field => $label)
                            <label class="check-chip">
                                <input type="hidden" name="{{ $field }}" value="0">
                                <input type="checkbox" name="{{ $field }}" value="1" {{ old($field, $partner->$field ?? false) ? 'checked' : '' }}>
                                <span class="check-chip__box">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg>
                                </span>
                                <span class="check-chip__label">{{ $label }}</span>
                            </label>
                        @endforeach
                    </div>
                    <p class="form-hint">The section only appears on a page if at least one partner is enabled for it.</p>
                </div>

            </div>
        </div>

        <div class="d-flex gap-2">
            <button type="submit" class="btn-brand">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg>
                {{ $editing ? 'Save Changes' : 'Add Partner' }}
            </button>
            <a href="{{ route('backend.partners.index') }}" class="btn-ghost">Cancel</a>
        </div>
    </form>

@endsection

@push('scripts')
    <script>
        // Live logo preview
        var input = document.getElementById('logo'), img = document.getElementById('logoPreview');
        if (input) input.addEventListener('change', function () {
            if (this.files && this.files[0]) {
                img.src = URL.createObjectURL(this.files[0]);
                img.style.display = 'block';
            }
        });
    </script>
@endpush
