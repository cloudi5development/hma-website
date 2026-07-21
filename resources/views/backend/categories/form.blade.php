@extends('backend.template.layouts.template-base')

@php $editing = $category->exists; @endphp

@section('title', $editing ? 'Edit Category' : 'Add Category')
@section('page_title', $editing ? 'Edit Category' : 'Add Category')
@section('page_sub', 'Categories')

@section('content')

    <div class="page-head">
        <a href="{{ route('backend.categories.index') }}" class="btn-ghost">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"><path d="m15 18-6-6 6-6"/></svg>
            Back to Categories
        </a>
    </div>

    <form method="POST"
          action="{{ $editing ? route('backend.categories.update', $category) : route('backend.categories.store') }}"
          enctype="multipart/form-data" novalidate>
        @csrf
        @if ($editing) @method('PUT') @endif

        <div class="row g-3">
            <div class="col-12 col-lg-8">
                <div class="hm-card">
                    <div class="hm-card__body">

                        <div class="form-row">
                            <label class="form-label" for="department_id">Department</label>
                            <select id="department_id" name="department_id"
                                    class="form-control-hm @error('department_id') is-invalid @enderror" required>
                                <option value="">— Select department —</option>
                                @foreach ($departments as $dept)
                                    <option value="{{ $dept->id }}" {{ (int) old('department_id', $category->department_id) === $dept->id ? 'selected' : '' }}>
                                        {{ $dept->name }}
                                    </option>
                                @endforeach
                            </select>
                            @error('department_id') <p class="form-error">{{ $message }}</p> @enderror
                        </div>

                        <div class="row g-3">
                            <div class="col-12 col-md-6">
                                <div class="form-row">
                                    <label class="form-label" for="name">Category Name</label>
                                    <input type="text" id="name" name="name"
                                           class="form-control-hm @error('name') is-invalid @enderror"
                                           value="{{ old('name', $category->name) }}" placeholder="e.g. IT & Software" required>
                                    @error('name') <p class="form-error">{{ $message }}</p> @enderror
                                </div>
                            </div>
                            <div class="col-12 col-md-6">
                                <div class="form-row">
                                    <label class="form-label" for="slug">Slug <span class="form-hint" style="display:inline">(optional)</span></label>
                                    <input type="text" id="slug" name="slug"
                                           class="form-control-hm @error('slug') is-invalid @enderror"
                                           value="{{ old('slug', $category->slug) }}" placeholder="auto from name">
                                    @error('slug') <p class="form-error">{{ $message }}</p> @enderror
                                </div>
                            </div>
                        </div>

                        <div class="form-row">
                            <label class="form-label" for="description">Description</label>
                            <textarea id="description" name="description" rows="3"
                                      class="form-control-hm @error('description') is-invalid @enderror"
                                      placeholder="Short summary of this category…">{{ old('description', $category->description) }}</textarea>
                            @error('description') <p class="form-error">{{ $message }}</p> @enderror
                        </div>

                        <div class="form-row" style="margin-bottom:0">
                            <label class="form-label" for="tone">Card Tone <span class="form-hint" style="display:inline">(home grid colour)</span></label>
                            <select id="tone" name="tone" class="form-control-hm @error('tone') is-invalid @enderror" style="max-width:220px">
                                <option value="">Auto</option>
                                @foreach (\App\Models\Category::TONES as $tone)
                                    <option value="{{ $tone }}" {{ old('tone', $category->tone) === $tone ? 'selected' : '' }}>{{ ucfirst($tone) }}</option>
                                @endforeach
                            </select>
                            @error('tone') <p class="form-error">{{ $message }}</p> @enderror
                        </div>

                    </div>
                </div>
            </div>

            <div class="col-12 col-lg-4">
                <div class="hm-card mb-3">
                    <div class="hm-card__head"><h2 class="hm-card__title">Icon</h2></div>
                    <div class="hm-card__body">
                        @if ($category->icon)
                            <div class="hm-media mb-3" style="max-width:120px"><img id="iconPreview" src="{{ $category->icon_url }}" alt=""></div>
                        @else
                            <div class="hm-media hm-media--empty mb-3" id="iconPreviewWrap" style="min-height:120px">
                                <img id="iconPreview" src="{{ asset('backend/template/images/actions/product-img.svg') }}" alt="">
                                No icon
                            </div>
                        @endif
                        <input type="file" id="icon" name="icon" accept="image/*"
                               class="form-control-hm @error('icon') is-invalid @enderror" style="height:auto;padding:9px 12px">
                        <p class="form-hint">WebP / PNG / JPG / SVG · max 2 MB.</p>
                        @error('icon') <p class="form-error">{{ $message }}</p> @enderror
                    </div>
                </div>

                <div class="hm-card mb-3">
                    <div class="hm-card__head"><h2 class="hm-card__title">Status</h2></div>
                    <div class="hm-card__body">
                        <label class="switch">
                            <input type="hidden" name="is_active" value="0">
                            <input type="checkbox" name="is_active" value="1" {{ old('is_active', $category->is_active ?? true) ? 'checked' : '' }}>
                            <span class="switch__track"></span>
                            <span class="switch__label">Active</span>
                        </label>
                        <div class="form-row mt-3" style="margin-bottom:0">
                            <label class="form-label" for="sort_order">Display Order</label>
                            <input type="number" id="sort_order" name="sort_order" min="0"
                                   class="form-control-hm @error('sort_order') is-invalid @enderror"
                                   value="{{ old('sort_order', $category->sort_order ?? 0) }}" style="max-width:140px">
                            @error('sort_order') <p class="form-error">{{ $message }}</p> @enderror
                        </div>
                    </div>
                </div>

                <div class="hm-card">
                    <div class="hm-card__head"><h2 class="hm-card__title">Visibility</h2></div>
                    <div class="hm-card__body d-flex flex-column gap-2">
                        <label class="check-chip">
                            <input type="hidden" name="show_home" value="0">
                            <input type="checkbox" name="show_home" value="1" {{ old('show_home', $category->show_home ?? false) ? 'checked' : '' }}>
                            <span class="check-chip__box">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg>
                            </span>
                            <span class="check-chip__label">Show on Home Page</span>
                        </label>
                        <label class="check-chip">
                            <input type="hidden" name="is_featured" value="0">
                            <input type="checkbox" name="is_featured" value="1" {{ old('is_featured', $category->is_featured ?? false) ? 'checked' : '' }}>
                            <span class="check-chip__box">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg>
                            </span>
                            <span class="check-chip__label">Feature in mega-menu</span>
                        </label>
                    </div>
                </div>
            </div>
        </div>

        <div class="mt-3 d-flex gap-2">
            <button type="submit" class="btn-brand">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg>
                {{ $editing ? 'Save Changes' : 'Add Category' }}
            </button>
            <a href="{{ route('backend.categories.index') }}" class="btn-ghost">Cancel</a>
        </div>
    </form>

@endsection

@push('scripts')
    <script>
        var input = document.getElementById('icon'), img = document.getElementById('iconPreview');
        if (input) input.addEventListener('change', function () {
            if (this.files && this.files[0]) {
                var wrap = document.getElementById('iconPreviewWrap');
                if (wrap) wrap.classList.remove('hm-media--empty');
                img.src = URL.createObjectURL(this.files[0]);
            }
        });
    </script>
@endpush
