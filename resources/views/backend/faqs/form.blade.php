@extends('backend.template.layouts.template-base')

@php $editing = $faq->exists; @endphp

@section('title', $editing ? 'Edit FAQ' : 'Add FAQ')
@section('page_title', $editing ? 'Edit FAQ' : 'Add FAQ')
@section('page_sub', 'FAQ')

@section('content')

    <div class="page-head">
        <a href="{{ route('backend.faqs.index') }}" class="btn-ghost">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"><path d="m15 18-6-6 6-6"/></svg>
            Back to FAQ
        </a>
    </div>

    <form method="POST"
          action="{{ $editing ? route('backend.faqs.update', $faq) : route('backend.faqs.store') }}"
          novalidate>
        @csrf
        @if ($editing) @method('PUT') @endif

        {{-- One container for the whole form --}}
        <div class="hm-card mb-3">
            <div class="hm-card__body">

                {{-- Row 1 — question + active toggle --}}
                <div class="row g-3">
                    <div class="col-12 col-md-6">
                        <div class="form-row" style="margin-bottom:0">
                            <label class="form-label" for="question">Question</label>
                            <input type="text" id="question" name="question"
                                   class="form-control-hm @error('question') is-invalid @enderror"
                                   value="{{ old('question', $faq->question) }}" placeholder="e.g. Will I receive placement assistance?" required>
                            @error('question') <p class="form-error">{{ $message }}</p> @enderror
                        </div>
                    </div>
                    <div class="col-12 col-md-6">
                        <div class="form-row" style="margin-bottom:0">
                            <label class="form-label">Status</label>
                            <div class="d-flex align-items-center" style="height:48px">
                                <label class="switch">
                                    <input type="hidden" name="is_active" value="0">
                                    <input type="checkbox" name="is_active" value="1" {{ old('is_active', $faq->is_active ?? true) ? 'checked' : '' }}>
                                    <span class="switch__track"></span>
                                    <span class="switch__label">Active</span>
                                </label>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Row 2 — answer --}}
                <div class="form-row mt-2">
                    <label class="form-label" for="answer">Answer</label>
                    <textarea id="answer" name="answer" rows="6"
                              class="form-control-hm @error('answer') is-invalid @enderror"
                              placeholder="A clear, reassuring answer…" required>{{ old('answer', $faq->answer) }}</textarea>
                    @error('answer') <p class="form-error">{{ $message }}</p> @enderror
                </div>

                {{-- Row 3 — display order --}}
                <div class="form-row">
                    <label class="form-label" for="sort_order">Display Order</label>
                    <input type="number" id="sort_order" name="sort_order" min="0"
                           class="form-control-hm @error('sort_order') is-invalid @enderror"
                           value="{{ old('sort_order', $faq->sort_order ?? 0) }}" style="max-width:160px">
                    <p class="form-hint">Lower numbers appear first in the accordion.</p>
                    @error('sort_order') <p class="form-error">{{ $message }}</p> @enderror
                </div>

                {{-- Row 4 — page visibility --}}
                <div class="form-row" style="margin-bottom:0">
                    <label class="form-label">Page Visibility</label>
                    <div class="d-flex flex-wrap gap-2">
                        @foreach (['show_home' => 'Home', 'show_about' => 'About', 'show_courses' => 'Courses', 'show_testimonials' => 'Testimonials'] as $field => $label)
                            <label class="check-chip">
                                <input type="hidden" name="{{ $field }}" value="0">
                                <input type="checkbox" name="{{ $field }}" value="1" {{ old($field, $faq->$field ?? false) ? 'checked' : '' }}>
                                <span class="check-chip__box">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg>
                                </span>
                                <span class="check-chip__label">{{ $label }}</span>
                            </label>
                        @endforeach
                    </div>
                    <p class="form-hint">The Contact page shows the same FAQs as Home.</p>
                </div>

            </div>
        </div>
        <div class="d-flex gap-2">
            <button type="submit" class="btn-brand">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg>
                {{ $editing ? 'Save Changes' : 'Add FAQ' }}
            </button>
            <a href="{{ route('backend.faqs.index') }}" class="btn-ghost">Cancel</a>
        </div>
    </form>

@endsection
