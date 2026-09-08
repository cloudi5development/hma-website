@extends('backend.template.layouts.template-base')

@section('title', $form->name)
@section('page_title', 'Form')
@section('page_sub', 'Forms')

@section('content')

    <div class="page-head">
        <a href="{{ route('backend.forms.index') }}" class="btn-ghost">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"><path d="m15 18-6-6 6-6"/></svg>
            Back to Forms
        </a>
    </div>

    {{-- ============================== HEADER ==============================
         What the form is, whether it is live, and where it lives — the three
         things you open this page to find out, answered before anything else. --}}
    <div class="hm-card fv-head mb-3">
        <div class="hm-card__body">
            <div class="fv-head__row">
                <div class="fv-head__main">
                    <span class="fv-status fv-status--{{ $form->status }}">
                        <span class="fv-status__dot" aria-hidden="true"></span>
                        {{ $form->status_label }}
                    </span>

                    <h1 class="fv-head__title">{{ $form->name }}</h1>

                    @if (filled($form->description))
                        <p class="fv-head__desc">{{ $form->description }}</p>
                    @endif

                    <p class="fv-head__meta">
                        Created {{ $form->created_at->format('d M Y') }}
                        · Last edited {{ $form->updated_at->format('d M Y, g:i a') }}
                        · {{ $form->fields->count() }} question{{ $form->fields->count() === 1 ? '' : 's' }}
                    </p>
                </div>

                {{-- The link, and the three things anyone does with it. --}}
                <div class="fv-head__link">
                    <label class="form-label" for="shareLink">Public link</label>
                    <input type="text" class="form-control-hm" readonly value="{{ $form->public_url }}" id="shareLink">
                    <div class="d-flex gap-2 mt-2 flex-wrap">
                        <button type="button" class="btn-brand" data-copy="#shareLink">Copy Link</button>
                        <a href="{{ $form->public_url }}" target="_blank" rel="noopener" class="btn-ghost">Open</a>
                        {{-- The only way to see a form that is not published —
                             its live link shows the closed message instead. --}}
                        <a href="{{ route('backend.forms.preview', $form) }}" class="btn-ghost">Preview</a>
                    </div>
                    @unless ($form->isPublished())
                        <p class="form-hint" style="margin-top:8px;color:#A6741F">
                            This form is <strong>{{ strtolower($form->status_label) }}</strong>, so the link shows
                            your closed message instead of the questions.
                        </p>
                    @endunless
                </div>
            </div>
        </div>
    </div>

    {{-- =============================== STATS =============================== --}}
    <div class="fv-stats mb-3">
        @foreach ([
            ['n' => $stats['total'],  'l' => 'Total responses', 'k' => 'total'],
            ['n' => $stats['unread'], 'l' => 'New / unactioned', 'k' => 'new'],
            ['n' => $stats['today'],  'l' => 'Today',            'k' => ''],
            ['n' => $stats['week'],   'l' => 'This week',        'k' => ''],
            ['n' => $stats['month'],  'l' => 'This month',       'k' => ''],
        ] as $stat)
            <div class="fv-stat @if ($stat['k']) fv-stat--{{ $stat['k'] }} @endif">
                <span class="fv-stat__n">{{ number_format($stat['n']) }}</span>
                <span class="fv-stat__l">{{ $stat['l'] }}</span>
            </div>
        @endforeach
    </div>

    @if ($form->max_submissions)
        <p class="form-hint mb-3">
            Capped at <strong>{{ number_format($form->max_submissions) }}</strong> responses —
            {{ number_format($form->remainingSubmissions()) }} still to go, after which the form closes itself.
        </p>
    @endif

    {{-- ============================= QUESTIONS =============================
         What the form actually asks, in the order it asks it. This page is for
         reading a form; everything that changes one is on the forms list or
         behind Edit. --}}
    <div class="hm-card">
        <div class="hm-card__head">
            <h2 class="hm-card__title">Questions</h2>
            <a href="{{ route('backend.forms.edit', $form) }}" class="btn-soft">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" style="width:15px;height:15px"><path d="M12 20h9"/><path d="M16.5 3.5a2.1 2.1 0 0 1 3 3L7 19l-4 1 1-4Z"/></svg>
                Edit
            </a>
        </div>

        @forelse ($form->fields as $i => $field)
            <div class="fv-q">
                <span class="fv-q__n">{{ $i + 1 }}</span>

                {{-- The type's own icon, from the same registry the builder's
                     dropdown and the public renderer read. --}}
                <span class="fv-q__icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7"
                         stroke-linecap="round" stroke-linejoin="round">{!! \App\Support\FormFieldType::icon($field->field_type) !!}</svg>
                </span>

                <div class="fv-q__body">
                    <p class="fv-q__label">
                        {{ $field->label }}
                        @if ($field->is_required) <span class="fv-q__req" title="Required">*</span> @endif
                    </p>

                    <p class="fv-q__meta">
                        <span class="pill pill--tiny">{{ $field->typeLabel() }}</span>

                        @if ($field->needsOptions())
                            <span>{{ $field->options->pluck('label')->implode(' · ') }}</span>
                        @elseif ($field->isGrid())
                            <span>{{ $field->rows->count() }} rows × {{ $field->columns->count() }} columns</span>
                        @elseif ($field->field_type === \App\Support\FormFieldType::LINEAR_SCALE)
                            <span>Scale {{ $field->scaleMin() }}–{{ $field->scaleMax() }}</span>
                        @elseif ($field->field_type === \App\Support\FormFieldType::RATING)
                            <span>{{ $field->ratingCount() }} {{ strtolower(\App\Support\FormFieldType::RATING_ICONS[$field->ratingIcon()]) }}</span>
                        @endif
                    </p>

                    @if ($field->condition())
                        <p class="fv-q__cond">
                            Shown only if <strong>{{ $field->condition()['field_key'] }}</strong>
                            {{ $field->condition()['operator'] === 'equals' ? 'is' : 'is not' }}
                            “{{ $field->condition()['value'] }}”
                        </p>
                    @endif
                </div>

                <code class="fv-q__key" title="How this answer is stored and exported">{{ $field->field_key }}</code>
            </div>
        @empty
            <div class="hm-card__body">
                <p class="hm-table__sub" style="margin:0">
                    No questions yet. <a href="{{ route('backend.forms.edit', $form) }}">Add the first one</a>.
                </p>
            </div>
        @endforelse
    </div>

@endsection

@push('scripts')
    <script>
        /* Copy the public link.
           execCommand is the fallback: navigator.clipboard is unavailable on a
           plain-HTTP origin, which is exactly what a staging panel often is. */
        document.querySelectorAll('[data-copy]').forEach(function (button) {
            button.addEventListener('click', function () {
                var target = document.querySelector(button.getAttribute('data-copy'));
                if (!target) return;

                target.select();
                target.setSelectionRange(0, 99999);

                var done = function () {
                    var original = button.textContent;
                    button.textContent = 'Copied';
                    setTimeout(function () { button.textContent = original; }, 1600);
                };

                if (navigator.clipboard && window.isSecureContext) {
                    navigator.clipboard.writeText(target.value).then(done, function () {
                        document.execCommand('copy');
                        done();
                    });
                } else {
                    document.execCommand('copy');
                    done();
                }
            });
        });
    </script>
@endpush

@push('styles')
    <style>
        /* ---- Header ---- */
        .fv-head__row { display: flex; gap: 32px; flex-wrap: wrap; align-items: flex-start; }
        .fv-head__main { flex: 1 1 320px; min-width: 0; }
        .fv-head__link { flex: 0 1 340px; min-width: 260px; }
        .fv-head__title {
            margin: 10px 0 6px;
            font-size: 26px;
            font-weight: 700;
            line-height: 1.2;
            color: var(--ink, #2E2620);
        }
        .fv-head__desc { margin: 0 0 10px; font-size: 14.5px; line-height: 1.7; color: var(--muted, #8A7E70); max-width: 60ch; }
        .fv-head__meta { margin: 0; font-size: 12.5px; color: #A2968A; }

        /* A dot plus a word, so the state does not rest on colour alone. */
        .fv-status {
            display: inline-flex;
            align-items: center;
            gap: 7px;
            padding: 5px 12px;
            border-radius: 999px;
            font-size: 12px;
            font-weight: 700;
            letter-spacing: .03em;
            text-transform: uppercase;
        }
        .fv-status__dot { width: 7px; height: 7px; border-radius: 50%; background: currentColor; }
        .fv-status--published { color: #2FA35F; background: #E4F7EC; }
        .fv-status--draft     { color: #A6741F; background: #FCEBD8; }
        .fv-status--disabled  { color: #8A7466; background: #F5EBE4; }

        /* ---- Stats ---- */
        .fv-stats { display: grid; grid-template-columns: repeat(5, minmax(0, 1fr)); gap: 12px; }
        .fv-stat {
            padding: 16px 18px;
            border: 1px solid var(--line, #E7DED2);
            border-radius: 14px;
            background: #fff;
        }
        .fv-stat__n { display: block; font-size: 26px; font-weight: 700; line-height: 1.1; color: var(--ink, #2E2620); }
        .fv-stat__l { display: block; margin-top: 4px; font-size: 12.5px; color: var(--muted, #8A7E70); }
        /* The two that carry a decision are tinted; the rest are context. */
        .fv-stat--total { background: linear-gradient(140deg, #FDF4EE, #fff); border-color: #EBD9CB; }
        .fv-stat--total .fv-stat__n { color: #843D21; }
        .fv-stat--new .fv-stat__n { color: #D98A1E; }

        /* ---- Question list ---- */
        .fv-q {
            display: flex;
            align-items: flex-start;
            gap: 12px;
            padding: 13px 20px;
            border-top: 1px solid #F2ECE4;
        }
        .fv-q:hover { background: #FDFAF7; }
        .fv-q__n { min-width: 18px; padding-top: 3px; font-size: 12.5px; font-weight: 700; color: #C0B4A8; }
        .fv-q__icon {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 30px;
            height: 30px;
            flex-shrink: 0;
            border-radius: 8px;
            background: #FBF3EC;
            color: #A85A2E;
        }
        .fv-q__icon svg { width: 17px; height: 17px; }
        .fv-q__body { flex: 1; min-width: 0; }
        .fv-q__label { margin: 0; font-size: 14.5px; font-weight: 600; color: var(--ink, #2E2620); }
        .fv-q__req { color: #C0392B; }
        .fv-q__meta {
            display: flex;
            align-items: center;
            gap: 8px;
            flex-wrap: wrap;
            margin: 4px 0 0;
            font-size: 12.5px;
            color: var(--muted, #8A7E70);
        }
        .fv-q__cond { margin: 5px 0 0; font-size: 12.5px; color: #A6741F; }
        .fv-q__key {
            flex-shrink: 0;
            padding: 3px 8px;
            border-radius: 6px;
            background: #F6F1EB;
            font-size: 11.5px;
            color: #8A7E70;
        }

        @media (max-width: 991.98px) {
            .fv-stats { grid-template-columns: repeat(2, minmax(0, 1fr)); }
        }
        @media (max-width: 575.98px) {
            .fv-stats { grid-template-columns: minmax(0, 1fr); }
            .fv-head__row { gap: 20px; }
            .fv-head__title { font-size: 22px; }
            .fv-q { padding: 12px 14px; }
            .fv-q__key { display: none; }
        }
    </style>
@endpush
