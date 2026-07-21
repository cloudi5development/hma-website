@extends('backend.template.layouts.template-base')

@php $editing = $counter->exists; @endphp

@section('title', $editing ? 'Edit Counter' : 'Add Counter')
@section('page_title', $editing ? 'Edit Counter' : 'Add Counter')
@section('page_sub', 'Counters')

@section('content')

    <div class="page-head">
        <a href="{{ route('backend.counters.index') }}" class="btn-ghost">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"><path d="m15 18-6-6 6-6"/></svg>
            Back to Counters
        </a>
    </div>

    <form method="POST"
          action="{{ $editing ? route('backend.counters.update', $counter) : route('backend.counters.store') }}"
          novalidate>
        @csrf
        @if ($editing) @method('PUT') @endif

        <div class="row g-3">
            <div class="col-12 col-lg-8">
                <div class="hm-card">
                    <div class="hm-card__body">

                        <div class="form-row">
                            <label class="form-label" for="number">Value</label>
                            <input type="text" id="number" name="number"
                                   class="form-control-hm @error('number') is-invalid @enderror"
                                   value="{{ old('number', $counter->number) }}" placeholder="e.g. 2.5K+" required
                                   style="max-width:220px">
                            <p class="form-hint">The figure shown, exactly as it should read: <code>2.5K+</code>, <code>150+</code>, <code>95%</code>. It counts up automatically.</p>
                            @error('number') <p class="form-error">{{ $message }}</p> @enderror
                        </div>

                        <div class="form-row">
                            <label class="form-label" for="label">Label</label>
                            <input type="text" id="label" name="label"
                                   class="form-control-hm @error('label') is-invalid @enderror"
                                   value="{{ old('label', $counter->label) }}" placeholder="e.g. Students Trained" required>
                            @error('label') <p class="form-error">{{ $message }}</p> @enderror
                        </div>

                        <div class="form-row">
                            <label class="form-label" for="sort_order">Display Order</label>
                            <input type="number" id="sort_order" name="sort_order" min="0"
                                   class="form-control-hm @error('sort_order') is-invalid @enderror"
                                   value="{{ old('sort_order', $counter->sort_order ?? 0) }}" style="max-width:160px">
                            <p class="form-hint">Lower numbers appear first (left to right).</p>
                            @error('sort_order') <p class="form-error">{{ $message }}</p> @enderror
                        </div>

                    </div>
                </div>
            </div>

            <div class="col-12 col-lg-4">
                <div class="hm-card mb-3">
                    <div class="hm-card__head"><h2 class="hm-card__title">Status</h2></div>
                    <div class="hm-card__body">
                        <label class="switch">
                            <input type="hidden" name="is_active" value="0">
                            <input type="checkbox" name="is_active" value="1" {{ old('is_active', $counter->is_active ?? true) ? 'checked' : '' }}>
                            <span class="switch__track"></span>
                            <span class="switch__label">Active (visible on site)</span>
                        </label>
                    </div>
                </div>

                <div class="hm-card">
                    <div class="hm-card__head"><h2 class="hm-card__title">Page Visibility</h2></div>
                    <div class="hm-card__body d-flex flex-column gap-2">
                        @foreach (['show_home' => 'Home', 'show_about' => 'About', 'show_testimonials' => 'Testimonials'] as $field => $label)
                            <label class="check-chip">
                                <input type="hidden" name="{{ $field }}" value="0">
                                <input type="checkbox" name="{{ $field }}" value="1" {{ old($field, $counter->$field ?? false) ? 'checked' : '' }}>
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

        <div class="mt-3 d-flex gap-2">
            <button type="submit" class="btn-brand">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg>
                {{ $editing ? 'Save Changes' : 'Add Counter' }}
            </button>
            <a href="{{ route('backend.counters.index') }}" class="btn-ghost">Cancel</a>
        </div>
    </form>

@endsection
