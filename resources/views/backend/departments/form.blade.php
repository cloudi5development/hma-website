@extends('backend.template.layouts.template-base')

@php
    $editing = $department->exists;

    // On a validation redisplay an empty tag-list posts nothing at all, so read
    // old input only when there is some — otherwise the picker would refill itself.
    $selected = collect(session()->hasOldInput() ? session()->getOldInput('categories', []) : $selected)
        ->map(fn ($id) => (int) $id)->all();

    // Feed the picker: every category with its current owner, so the admin can
    // see that picking one will move it out of another department.
    $pickerData = $allCategories->map(fn ($c) => [
        'id'    => $c->id,
        'name'  => $c->name,
        'icon'  => $c->icon_url,
        'owner' => $c->department?->id === $department->id ? '' : ($c->department?->name ?? 'unassigned'),
    ])->values();
@endphp

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
          novalidate>
        @csrf
        @if ($editing) @method('PUT') @endif

        <div class="hm-card mb-3">
            <div class="hm-card__body">

                {{-- Row 1 — name + active toggle --}}
                <div class="row g-3 align-items-start">
                    <div class="col-12 col-md-6">
                        <div class="form-row" style="margin-bottom:0">
                            <label class="form-label" for="name">
                                Department Name <span style="color:var(--danger)">*</span>
                            </label>
                            <input type="text" id="name" name="name"
                                   class="form-control-hm @error('name') is-invalid @enderror"
                                   value="{{ old('name', $department->name) }}" placeholder="e.g. Technical" required>
                            @error('name') <p class="form-error">{{ $message }}</p> @enderror
                        </div>
                    </div>
                    <div class="col-12 col-md-6">
                        <div class="form-row" style="margin-bottom:0">
                            <label class="form-label">Status</label>
                            {{-- Wrapper matches the name input's 48px height so the two line up --}}
                            <div class="d-flex align-items-center" style="height:48px">
                                <label class="switch">
                                    <input type="hidden" name="is_active" value="0">
                                    <input type="checkbox" name="is_active" value="1" {{ old('is_active', $department->is_active ?? true) ? 'checked' : '' }}>
                                    <span class="switch__track"></span>
                                    <span class="switch__label">Active</span>
                                </label>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Row 2 — search an existing category and add it to this department --}}
                <div class="form-row mt-4" style="margin-bottom:0">
                    <label class="form-label" for="categorySearch">Categories</label>

                    @if ($allCategories->isEmpty())
                        <p class="form-hint" style="margin-top:0">
                            No categories yet —
                            <a href="{{ route('backend.categories.create') }}">create one first</a>,
                            then come back to add it here.
                        </p>
                    @else
                        @include('backend.partials.entity-picker', [
                            'key'         => 'category',
                            'name'        => 'categories',
                            'items'       => $pickerData,
                            'selected'    => $selected,
                            'placeholder' => 'Search categories…',
                            'hint'        => 'Search by name and click a result to add it. Adding a category that sits under another department moves it here; removing its tag leaves it unassigned.',
                        ])
                    @endif

                    @error('categories') <p class="form-error">{{ $message }}</p> @enderror
                    @error('categories.*') <p class="form-error">{{ $message }}</p> @enderror
                </div>

            </div>
        </div>

        <div class="d-flex gap-2">
            <button type="submit" class="btn-brand">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg>
                {{ $editing ? 'Save Changes' : 'Add Department' }}
            </button>
            <a href="{{ route('backend.departments.index') }}" class="btn-ghost">Cancel</a>
        </div>
    </form>

@endsection
