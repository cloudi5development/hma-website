@extends('backend.template.layouts.template-base')

@section('title', 'Response #' . $response->id)
@section('page_title', 'Response #' . $response->id)
@section('page_sub', $form->name)

@section('content')

    <div class="page-head">
        <a href="{{ route('backend.forms.responses.index', $form) }}" class="btn-ghost">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"><path d="m15 18-6-6 6-6"/></svg>
            Back to Responses
        </a>
    </div>

    <div class="row g-3">
        <div class="col-12 col-lg-8">
            <div class="hm-card">
                <div class="hm-card__head">
                    <h2 class="hm-card__title">{{ $form->title }}</h2>
                    <span class="pill pill--tiny pill--{{ $response->status_slug }}">{{ $response->status }}</span>
                </div>
                <div class="hm-card__body">

                    @if ($response->values->isEmpty())
                        <p class="hm-table__sub" style="margin:0">This response was submitted with every field left blank.</p>
                    @else
                        <table class="hm-table">
                            <tbody>
                                @foreach ($response->values as $value)
                                    <tr>
                                        {{-- The label is the snapshot taken when
                                             this was submitted, so it reads as the
                                             question this person was actually
                                             asked even if it has been reworded
                                             since. --}}
                                        <td style="width:220px;color:#6b6357;vertical-align:top">
                                            {{ $value->field_label }}
                                            {{-- The relation loads removed
                                                 (soft-deleted) questions too, so
                                                 "removed" means trashed — a
                                                 missing row almost never
                                                 happens. --}}
                                            @if (! $value->field || $value->field->trashed())
                                                <span class="hm-table__sub d-block">(field since removed)</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if ($value->isFile())
                                                {{-- Files are held off the public
                                                     disk, so these links go through
                                                     the admin-only download route
                                                     rather than at a URL anyone
                                                     could guess. --}}
                                                @forelse ($value->filePaths() as $i => $path)
                                                    <a href="{{ route('backend.forms.responses.file', [$form, $response, $value, $i]) }}"
                                                       class="btn-ghost btn-sm d-inline-flex" style="margin:0 6px 6px 0">
                                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" style="width:15px;height:15px">
                                                            <path d="M12 3v12m0 0 4-4m-4 4-4-4M4 17v2a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-2"/>
                                                        </svg>
                                                        {{ \App\Models\FormResponseValue::originalName($path) }}
                                                    </a>
                                                @empty
                                                    <span class="hm-table__sub">—</span>
                                                @endforelse
                                            @elseif ($value->isGrid())
                                                {{-- A grid squeezed onto one line
                                                     is unreadable at six rows. On
                                                     the detail page there is room
                                                     to show it as what it is. --}}
                                                @forelse ($value->gridAnswers() as $gridRow => $picked)
                                                    <div style="display:flex;gap:10px;padding:3px 0">
                                                        <span style="min-width:140px;color:#6b6357">{{ $gridRow }}</span>
                                                        <strong style="font-weight:600">{{ implode(', ', $picked) }}</strong>
                                                    </div>
                                                @empty
                                                    <span class="hm-table__sub">—</span>
                                                @endforelse
                                            @else
                                                {{-- Escaped, and whitespace kept:
                                                     a long-text answer arrives with
                                                     the line breaks it was typed
                                                     with. --}}
                                                <span style="white-space:pre-wrap">{{ $value->display ?: '—' }}</span>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    @endif

                    {{-- Questions this person was never asked — added to the form
                         after they submitted. Named rather than left as a silent
                         gap, so a blank column in the export is explainable. --}}
                    @php
                        $missing = $form->fields->reject(fn ($f) => $response->answerFor($f) !== null);
                    @endphp
                    @if ($missing->isNotEmpty())
                        <p class="form-hint" style="margin:16px 0 0">
                            Not answered: {{ $missing->pluck('label')->implode(', ') }}.
                            A field added after this was submitted, or one hidden by your conditions at the time.
                        </p>
                    @endif
                </div>
            </div>
        </div>

        <div class="col-12 col-lg-4">
            <div class="hm-card mb-3">
                <div class="hm-card__head"><h2 class="hm-card__title">Status</h2></div>
                <div class="hm-card__body">
                    <form method="POST" action="{{ route('backend.forms.responses.status', [$form, $response]) }}">
                        @csrf @method('PATCH')
                        <select name="status" class="form-control-hm mb-2">
                            @foreach ($statuses as $s)
                                <option value="{{ $s }}" {{ $response->status === $s ? 'selected' : '' }}>{{ $s }}</option>
                            @endforeach
                        </select>
                        <button type="submit" class="btn-brand" style="width:100%;justify-content:center">Update Status</button>
                    </form>
                </div>
            </div>

            <div class="hm-card mb-3">
                <div class="hm-card__head"><h2 class="hm-card__title">Details</h2></div>
                <div class="hm-card__body">
                    <table class="hm-table">
                        <tbody>
                            <tr><td style="width:110px;color:#6b6357">Response</td><td>#{{ $response->id }}</td></tr>
                            <tr><td style="color:#6b6357">Submitted</td><td>{{ $response->submitted_label }}</td></tr>
                            <tr><td style="color:#6b6357">IP address</td><td>{{ $response->ip_address ?: '—' }}</td></tr>
                            <tr><td style="color:#6b6357">Form</td><td><a href="{{ route('backend.forms.show', $form) }}">{{ $form->name }}</a></td></tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="hm-card">
                <div class="hm-card__body">
                    <form method="POST" action="{{ route('backend.forms.responses.destroy', [$form, $response]) }}"
                          data-confirm="Delete this response? Any files uploaded with it are deleted too. This cannot be undone.">
                        @csrf @method('DELETE')
                        <button type="submit" class="btn-danger-soft" style="width:100%;justify-content:center">Delete Response</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

@endsection
