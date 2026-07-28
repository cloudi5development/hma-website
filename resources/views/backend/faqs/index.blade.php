@extends('backend.template.layouts.template-base')

@php $atMax = $faqs->total() >= \App\Models\Faq::MAX; @endphp

@section('title', 'FAQ')
@section('page_title', 'FAQ')
@section('page_sub', 'Accordion shown on the home, about, contact and course pages (max ' . \App\Models\Faq::MAX . ')')

@section('content')

    <div class="page-head">
        <div>
            <h1 class="page-head__title">FAQ</h1>
            <p class="page-head__sub">{{ $faqs->total() }} of {{ \App\Models\Faq::MAX }} question{{ $faqs->total() === 1 ? '' : 's' }}</p>
        </div>
        @if ($atMax)
            <button type="button" class="btn-brand is-disabled" data-faq-max>
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round"><path d="M12 5v14M5 12h14"/></svg>
                Add FAQ
            </button>
        @else
            <a href="{{ route('backend.faqs.create') }}" class="btn-brand">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round"><path d="M12 5v14M5 12h14"/></svg>
                Add FAQ
            </a>
        @endif
    </div>



    @if ($atMax)
        <div class="alert-hm alert-hm--error">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="M12 8v5M12 16h.01"/></svg>
            You've reached the maximum of {{ \App\Models\Faq::MAX }} FAQs. Delete one before adding another.
        </div>
    @endif

    <div class="hm-card">
        @include("backend.partials.table-toolbar", ["placeholder" => "Search question"])
        <div class="table-responsive">
            <table class="hm-table">
                <thead>
                    <tr>
                        <th>Question</th><th>Visible On</th><th>Status</th><th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($faqs as $faq)
                        <tr>
                            <td class="hm-table__name">{{ $faq->question }}</td>
                            <td>
                                <span class="flag-text">
                                    {{ collect([$faq->show_home ? "Home" : null, $faq->show_about ? "About" : null, $faq->show_courses ? "Courses" : null, $faq->show_testimonials ? "Testimonials" : null])->filter()->implode(" · ") ?: "—" }}
                                </span>
                            </td>
                            <td>
                                <span class="pill pill--tiny {{ $faq->is_active ? 'pill--active' : 'pill--inactive' }}">
                                    {{ $faq->is_active ? 'Active' : 'Hidden' }}
                                </span>
                            </td>
                            <td class="text-end">
                                <div class="d-inline-flex gap-2">
                                    <a href="{{ route('backend.faqs.edit', $faq) }}" class="btn-ghost btn-icon" aria-label="Edit">
                                        <span class="act-ico act-ico--edit" aria-hidden="true"></span>
                                    </a>
                                    <form method="POST" action="{{ route('backend.faqs.destroy', $faq) }}"
                                          data-confirm="Delete this FAQ?" class="d-inline">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="btn-danger-soft btn-icon" aria-label="Delete">
                                            <span class="act-ico act-ico--delete" aria-hidden="true"></span>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="text-center py-5 hm-table__sub">No FAQs yet. Add your first one.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

@endsection

@push('scripts')
    <script>
        // At the cap, the Add button pops an alert instead of navigating.
        var maxBtn = document.querySelector('[data-faq-max]');
        if (maxBtn) maxBtn.addEventListener('click', function () {
            window.alert('Maximum {{ \App\Models\Faq::MAX }} FAQs allowed.');
        });
    </script>
@endpush
