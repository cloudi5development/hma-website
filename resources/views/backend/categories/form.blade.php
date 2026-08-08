@extends('backend.template.layouts.template-base')

@php
    $editing = $category->exists;

    // On a validation redisplay an empty tag-list posts nothing at all, so read
    // old input only when there is some — otherwise the picker would refill itself.
    $selectedCourses = collect(session()->hasOldInput() ? session()->getOldInput('courses', []) : $selected)
        ->map(fn ($id) => (int) $id)->all();

    // Feed the picker: every course with its current owner, so the admin can see
    // that picking one will move it out of another category.
    $coursePickerData = $allCourses->map(fn ($c) => [
        'id'    => $c->id,
        'name'  => $c->name,
        'icon'  => $c->image_url,
        'owner' => $c->category?->id === $category->id ? '' : ($c->category?->name ?? 'unassigned'),
    ])->values();
@endphp

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

        <div class="hm-card mb-3">
            <div class="hm-card__body">

                {{-- Row 1 — name + department --}}
                <div class="row g-3">
                    <div class="col-12 col-md-6">
                        <div class="form-row" style="margin-bottom:0">
                            <label class="form-label" for="name">
                                Category Name <span style="color:var(--danger)">*</span>
                            </label>
                            <input type="text" id="name" name="name"
                                   class="form-control-hm @error('name') is-invalid @enderror"
                                   value="{{ old('name', $category->name) }}" placeholder="e.g. IT & Software" required>
                            @error('name') <p class="form-error">{{ $message }}</p> @enderror
                        </div>
                    </div>
                    <div class="col-12 col-md-6">
                        <div class="form-row" style="margin-bottom:0">
                            <label class="form-label" for="department_id">
                                Department <span style="color:var(--danger)">*</span>
                            </label>
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
                    </div>
                </div>

                {{-- Row 2 — description + active toggle. Same 6/6 split as row 1 so
                     the right-hand fields start on the same line in both rows. --}}
                <div class="row g-3 mt-2">
                    <div class="col-12 col-md-6">
                        <div class="form-row" style="margin-bottom:0">
                            <label class="form-label" for="description">Description</label>
                            <textarea id="description" name="description" rows="3"
                                      class="form-control-hm @error('description') is-invalid @enderror"
                                      placeholder="Short summary of this category…">{{ old('description', $category->description) }}</textarea>
                            @error('description') <p class="form-error">{{ $message }}</p> @enderror
                        </div>
                    </div>
                    <div class="col-12 col-md-6">
                        <div class="form-row" style="margin-bottom:0">
                            <label class="form-label">Status</label>
                            <div class="d-flex align-items-center" style="height:48px">
                                <label class="switch">
                                    <input type="hidden" name="is_active" value="0">
                                    <input type="checkbox" name="is_active" value="1" {{ old('is_active', $category->is_active ?? true) ? 'checked' : '' }}>
                                    <span class="switch__track"></span>
                                    <span class="switch__label">Active</span>
                                </label>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Row 3 — visibility. The card's pastel colour is not here: it is
                     handed out automatically in palette order when the category is
                     created, so the home grid varies without anyone choosing. --}}
                <div class="form-row mt-4" style="margin-bottom:0">
                    <label class="form-label">Visibility</label>
                    <div class="d-flex flex-wrap align-items-center gap-2">
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
                    <p class="form-hint">
                        The card's pastel colour is assigned automatically — each new category takes
                        the next shade in the palette, so the home grid stays varied.
                    </p>
                </div>

                {{-- Row 4 — icon --}}
                <div class="form-row mt-4" style="margin-bottom:0">
                    <label class="form-label" for="icon">
                        Icon @unless ($editing) <span style="color:var(--danger)">*</span> @endunless
                    </label>
                    <div class="d-flex align-items-center gap-3 flex-wrap">
                        <span class="tbl-logo" style="width:64px;height:64px;border-radius:14px">
                            <img id="iconPreview" src="{{ $category->icon_url ?: asset('backend/template/images/actions/product-img.svg') }}" alt="">
                        </span>
                        <div>
                            <input type="file" id="icon" name="icon" accept="image/*"
                                   class="form-control-hm @error('icon') is-invalid @enderror" style="height:auto;padding:9px 12px">
                            <p class="form-hint">WebP / PNG / JPG / SVG · max 2 MB.</p>
                        </div>
                    </div>
                    @error('icon') <p class="form-error">{{ $message }}</p> @enderror
                </div>

                {{-- Row 5 — search an existing course and add it to this category --}}
                <div class="form-row mt-4" style="margin-bottom:0">
                    <label class="form-label" for="courseSearch">Courses</label>

                    @if ($allCourses->isEmpty())
                        <p class="form-hint" style="margin-top:0">
                            No courses yet —
                            <a href="{{ route('backend.courses.create') }}">create one first</a>,
                            then come back to add it here.
                        </p>
                    @else
                        @include('backend.partials.entity-picker', [
                            'key'         => 'course',
                            'name'        => 'courses',
                            'items'       => $coursePickerData,
                            'selected'    => $selectedCourses,
                            'placeholder' => 'Search courses…',
                            'hint'        => 'Search by name and click a result to add it. Adding a course that sits under another category moves it here; removing its tag leaves it unassigned.',
                        ])
                    @endif

                    @error('courses') <p class="form-error">{{ $message }}</p> @enderror
                    @error('courses.*') <p class="form-error">{{ $message }}</p> @enderror
                </div>

            </div>
        </div>

        <div class="d-flex gap-2">
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
            if (this.files && this.files[0]) img.src = URL.createObjectURL(this.files[0]);
        });
    </script>
@endpush
