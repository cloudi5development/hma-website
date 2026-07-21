@extends('backend.template.layouts.template-base')

@php $editing = $department->exists; @endphp

@section('title', $editing ? 'Edit Department' : 'Add Department')
@section('page_title', $editing ? 'Edit Department' : 'Add Department')
@section('page_sub', 'Departments')

@section('content')

    <div class="page-head">
        <a href="{{ route('backend.departments.index') }}" class="btn-ghost">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"><path d="m15 18-6-6 6-6"/></svg>
            Back to Departments
        </a>
    </div>

    <form method="POST"
          action="{{ $editing ? route('backend.departments.update', $department) : route('backend.departments.store') }}"
          enctype="multipart/form-data" novalidate>
        @csrf
        @if ($editing) @method('PUT') @endif

        <div class="row g-3">
            <div class="col-12 col-lg-8">
                <div class="hm-card">
                    <div class="hm-card__body">

                        <div class="form-row">
                            <label class="form-label" for="name">Department Name</label>
                            <input type="text" id="name" name="name"
                                   class="form-control-hm @error('name') is-invalid @enderror"
                                   value="{{ old('name', $department->name) }}" placeholder="e.g. Technical" required>
                            @error('name') <p class="form-error">{{ $message }}</p> @enderror
                        </div>

                        <div class="form-row">
                            <label class="form-label" for="slug">Slug <span class="form-hint" style="display:inline">(optional — auto from name)</span></label>
                            <input type="text" id="slug" name="slug"
                                   class="form-control-hm @error('slug') is-invalid @enderror"
                                   value="{{ old('slug', $department->slug) }}" placeholder="e.g. technical">
                            @error('slug') <p class="form-error">{{ $message }}</p> @enderror
                        </div>

                        <div class="form-row">
                            <label class="form-label" for="description">Description <span class="form-hint" style="display:inline">(optional)</span></label>
                            <textarea id="description" name="description" rows="3"
                                      class="form-control-hm @error('description') is-invalid @enderror"
                                      placeholder="Short summary of this domain…">{{ old('description', $department->description) }}</textarea>
                            @error('description') <p class="form-error">{{ $message }}</p> @enderror
                        </div>

                    </div>
                </div>
            </div>

            <div class="col-12 col-lg-4">
                <div class="hm-card mb-3">
                    <div class="hm-card__head"><h2 class="hm-card__title">Mega-menu Image</h2></div>
                    <div class="hm-card__body">
                        @if ($department->image)
                            <div class="hm-media mb-3"><img id="deptPreview" src="{{ $department->image_url }}" alt=""></div>
                        @else
                            <div class="hm-media hm-media--empty mb-3" id="deptPreviewWrap">
                                <img id="deptPreview" src="{{ asset('backend/template/images/actions/product-img.svg') }}" alt="">
                                No image uploaded
                            </div>
                        @endif
                        <input type="file" id="image" name="image" accept="image/*"
                               class="form-control-hm @error('image') is-invalid @enderror" style="height:auto;padding:9px 12px">
                        <p class="form-hint">WebP / PNG / JPG · max 2 MB · shown at the top of this department's mega-menu column.</p>
                        @error('image') <p class="form-error">{{ $message }}</p> @enderror
                    </div>
                </div>

                <div class="hm-card mb-3">
                    <div class="hm-card__head"><h2 class="hm-card__title">Status</h2></div>
                    <div class="hm-card__body">
                        <label class="switch">
                            <input type="hidden" name="is_active" value="0">
                            <input type="checkbox" name="is_active" value="1" {{ old('is_active', $department->is_active ?? true) ? 'checked' : '' }}>
                            <span class="switch__track"></span>
                            <span class="switch__label">Active</span>
                        </label>
                        <div class="form-row mt-3" style="margin-bottom:0">
                            <label class="form-label" for="sort_order">Display Order</label>
                            <input type="number" id="sort_order" name="sort_order" min="0"
                                   class="form-control-hm @error('sort_order') is-invalid @enderror"
                                   value="{{ old('sort_order', $department->sort_order ?? 0) }}" style="max-width:140px">
                            @error('sort_order') <p class="form-error">{{ $message }}</p> @enderror
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="mt-3 d-flex gap-2">
            <button type="submit" class="btn-brand">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg>
                {{ $editing ? 'Save Changes' : 'Add Department' }}
            </button>
            <a href="{{ route('backend.departments.index') }}" class="btn-ghost">Cancel</a>
        </div>
    </form>

@endsection

@push('scripts')
    <script>
        var input = document.getElementById('image'), img = document.getElementById('deptPreview');
        if (input) input.addEventListener('change', function () {
            if (this.files && this.files[0]) {
                var wrap = document.getElementById('deptPreviewWrap');
                if (wrap) wrap.classList.remove('hm-media--empty');
                img.src = URL.createObjectURL(this.files[0]);
            }
        });
    </script>
@endpush
