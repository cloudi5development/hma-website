@extends('backend.template.layouts.template-base')

@section('title', 'Forms')
@section('page_title', 'Forms')
@section('page_sub', 'Build a form, share its link, collect responses')

@section('content')

    <div class="page-head">
        <div>
            <h1 class="page-head__title">Forms</h1>
            <p class="page-head__sub">
                {{ $forms->total() }} form{{ $forms->total() === 1 ? '' : 's' }}
            </p>
        </div>
        <a href="{{ route('backend.forms.create') }}" class="btn-brand">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round"><path d="M12 5v14M5 12h14"/></svg>
            Create Form
        </a>
    </div>

    <div class="hm-card">
        {{-- The status filter is the form's three-way state, not the Active/Hidden
             boolean the shared toolbar assumes, so its options are passed in. --}}
        @include('backend.partials.table-toolbar', [
            'placeholder' => 'Search form name, title or link',
            'statuses'    => \App\Models\Form::STATUSES,
        ])
        <div class="table-responsive">
            <table class="hm-table">
                <thead>
                    <tr>
                        <th>#</th><th>Form</th><th>Copy Link</th><th>Responses</th>
                        <th>Status</th><th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($forms as $form)
                        <tr>
                            <td>#{{ $form->id }}</td>
                            <td class="hm-table__name">
                                {{ $form->name }}
                                {{-- The internal name and the public title are
                                     different jobs and usually different words, so
                                     both are shown rather than guessing which the
                                     admin is looking for. --}}
                                <span class="hm-table__sub d-block">{{ $form->title }}</span>
                            </td>
                            {{-- The link, and one click to copy it. The full URL
                                 rides on the button so nothing has to be
                                 reconstructed in JavaScript. --}}
                            <td>
                                <span class="fb-copycell">
                                    <button type="button" class="fb-copybtn" data-copy-url="{{ $form->public_url }}"
                                            aria-label="Copy the link to {{ $form->name }}"
                                            title="Copy link">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                                            <rect x="9" y="9" width="12" height="12" rx="2"/><path d="M5 15V5a2 2 0 0 1 2-2h8"/>
                                        </svg>
                                    </button>
                                    @if ($form->isPublished())
                                        <a href="{{ $form->public_url }}" target="_blank" rel="noopener" class="hm-table__sub">/forms/{{ $form->slug }}</a>
                                    @else
                                        <span class="hm-table__sub">/forms/{{ $form->slug }}</span>
                                    @endif
                                </span>
                            </td>
                            {{-- Counted, never stored: a response that arrived a
                                 moment ago shows here with nothing recalculated. --}}
                            <td>
                                @if ($form->responses_count)
                                    <a href="{{ route('backend.forms.responses.index', $form) }}" class="hm-table__sub">
                                        <strong>{{ $form->responses_count }}</strong>
                                    </a>
                                @else
                                    <span class="hm-table__sub">0</span>
                                @endif
                            </td>
                            <td>
                                <span class="pill pill--tiny {{ $form->isPublished() ? 'pill--active' : 'pill--inactive' }}">
                                    {{ $form->status_label }}
                                </span>
                            </td>
                            {{-- Four actions, on one row. Preview, publish/disable
                                 and duplicate all still exist — they live on the
                                 form's own page (the eye), where there is room to
                                 label them and to say what they will do. Seven
                                 unlabelled icons in a table cell wrapped onto
                                 three lines and told nobody anything. --}}
                            <td class="text-end">
                                <div class="fb-actions-cell">
                                    <a href="{{ route('backend.forms.show', $form) }}" class="btn-ghost btn-icon" aria-label="View" title="View">
                                        <span class="act-ico act-ico--view" aria-hidden="true"></span>
                                    </a>
                                    <a href="{{ route('backend.forms.edit', $form) }}" class="btn-ghost btn-icon" aria-label="Edit" title="Edit">
                                        <span class="act-ico act-ico--edit" aria-hidden="true"></span>
                                    </a>
                                    <a href="{{ route('backend.forms.responses.index', $form) }}" class="btn-ghost btn-icon" aria-label="Responses" title="Responses">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" style="width:16px;height:16px">
                                            <path d="M4 5h16M4 12h16M4 19h10"/>
                                        </svg>
                                    </a>

                                    <form method="POST" action="{{ route('backend.forms.destroy', $form) }}" class="d-inline"
                                          data-confirm="Delete “{{ $form->name }}”? This also deletes its {{ $form->fields_count }} field(s) and {{ $form->responses_count }} response(s). This cannot be undone.">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="btn-danger-soft btn-icon" aria-label="Delete" title="Delete">
                                            <span class="act-ico act-ico--delete" aria-hidden="true"></span>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center py-5 hm-table__sub">
                                No forms yet. <a href="{{ route('backend.forms.create') }}">Create your first one</a> —
                                you decide every question it asks.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    @include('backend.partials.table-pagination', ['paginator' => $forms])

@endsection

@push('scripts')
    <script>
        /* Copy a form's link straight from the table.
           navigator.clipboard is unavailable on a plain-HTTP origin — which a
           staging panel often is — so a hidden textarea and execCommand is the
           fallback rather than leaving the button silently dead. */
        document.querySelectorAll('[data-copy-url]').forEach(function (button) {
            button.addEventListener('click', function () {
                var url = button.getAttribute('data-copy-url');

                var done = function () {
                    button.classList.add('is-copied');
                    setTimeout(function () { button.classList.remove('is-copied'); }, 1600);
                    if (window.hmToast) window.hmToast('Link copied: ' + url, 'success', 'Copied');
                };

                if (navigator.clipboard && window.isSecureContext) {
                    navigator.clipboard.writeText(url).then(done, function () { fallback(url, done); });
                } else {
                    fallback(url, done);
                }
            });
        });

        function fallback(text, done) {
            var box = document.createElement('textarea');
            box.value = text;
            box.setAttribute('readonly', '');
            box.style.position = 'fixed';
            box.style.left = '-9999px';
            document.body.appendChild(box);
            box.select();
            try { document.execCommand('copy'); done(); } catch (e) { window.prompt('Copy this link:', text); }
            document.body.removeChild(box);
        }
    </script>
@endpush

@push('styles')
    <style>
        /* The action icons stay on one line. nowrap is the point of the rule:
           without it a narrow Actions column wraps them into a vertical stack,
           which is what this cell used to do. */
        .fb-actions-cell {
            display: inline-flex;
            align-items: center;
            justify-content: flex-end;
            flex-wrap: nowrap;
            gap: 6px;
            white-space: nowrap;
        }
        .fb-actions-cell > * { flex-shrink: 0; }

        .fb-copycell { display: inline-flex; align-items: center; gap: 8px; }
        .fb-copybtn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 30px;
            height: 30px;
            flex-shrink: 0;
            border: 1px solid var(--line, #E7DED2);
            border-radius: 8px;
            background: #fff;
            color: var(--muted, #8A7E70);
            cursor: pointer;
            transition: border-color .15s ease, color .15s ease, background .15s ease;
        }
        .fb-copybtn svg { width: 15px; height: 15px; }
        .fb-copybtn:hover { border-color: #A85A2E; color: #A85A2E; background: #FDF4EE; }
        /* A moment of confirmation, so the click is not silent. */
        .fb-copybtn.is-copied { border-color: #2FA35F; color: #2FA35F; background: #E4F7EC; }
    </style>
@endpush
