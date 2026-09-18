@extends('backend.template.layouts.template-base')

@section('title', 'Form Responses')
@section('page_title', 'Responses')
@section('page_sub', 'Everything submitted through your forms')

@section('content')

    <div class="page-head">
        <div>
            <h1 class="page-head__title">Responses</h1>
            <p class="page-head__sub">
                {{ $responses->total() }} response{{ $responses->total() === 1 ? '' : 's' }}
                @if ($formId && $forms->firstWhere('id', $formId))
                    · for <strong>{{ $forms->firstWhere('id', $formId)->name }}</strong>
                @endif
            </p>
        </div>
        <div class="d-inline-flex gap-2 flex-wrap">
            @if ($formId && $forms->firstWhere('id', $formId))
                {{-- Exporting needs a form: the columns are its questions, and
                     there is no shared set of columns across two different
                     forms. --}}
                {{-- With the search, status and date on screen, so the file holds
                     what the page is showing — the count above says so. --}}
                <a href="{{ route('backend.forms.responses.export-excel', [$formId] + array_filter(request()->only(['q', 'status', 'date']), 'filled')) }}" class="btn-brand">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3v12m0 0 4-4m-4 4-4-4M4 17v2a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-2"/></svg>
                    Export This Form
                </a>
            @endif

            {{-- Clears exactly what the filters are showing. With no filter set
                 that is every response of every form, so the confirmation says
                 so in as many words. --}}
            @if ($responses->total())
                @php
                    $filtered = collect(['q', 'status', 'date', 'form'])->contains(fn ($k) => filled(request($k)));
                    $scope    = $filtered
                        ? 'the ' . number_format($responses->total()) . ' response(s) these filters are showing'
                        : 'all ' . number_format($responses->total()) . ' response(s), across every form';
                @endphp
                <form method="POST" action="{{ route('backend.forms.clear-responses', request()->query()) }}"
                      class="d-inline"
                      data-confirm="Delete {{ $scope }}, including any uploaded files? This cannot be undone."
                      data-confirm-title="Clear responses?"
                      data-confirm-label="Delete {{ number_format($responses->total()) }}">
                    @csrf @method('DELETE')
                    <button type="submit" class="btn-danger-soft">
                        {{ $filtered ? 'Delete Filtered' : 'Clear All' }}
                    </button>
                </form>
            @endif
        </div>
    </div>

    {{-- Filters: which form, and which day. Search and status live in the
         shared toolbar below. --}}
    <div class="hm-card mb-3">
        <div class="hm-card__body">
            <form method="GET" class="row g-2 align-items-end">
                {{-- The search, status and page size from the toolbar ride
                     along, so filtering by day does not quietly drop them. --}}
                @foreach (['q', 'status', 'per_page'] as $carried)
                    @if (filled(request($carried)))
                        <input type="hidden" name="{{ $carried }}" value="{{ request($carried) }}">
                    @endif
                @endforeach
                <div class="col-12 col-md-4">
                    <label class="form-label" for="form">Form</label>
                    <select id="form" name="form" class="form-control-hm">
                        <option value="">All forms</option>
                        @foreach ($forms as $option)
                            <option value="{{ $option->id }}" @selected($formId === $option->id)>{{ $option->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-12 col-md-3">
                    <label class="form-label" for="date">Date</label>
                    <input type="date" id="date" name="date" value="{{ request('date') }}" class="form-control-hm">
                </div>
                <div class="col-12 col-md-3 d-flex gap-2">
                    <button type="submit" class="btn-brand" style="flex:1;justify-content:center">Filter</button>
                    <a href="{{ route('backend.forms.all-responses') }}" class="btn-ghost">Clear</a>
                </div>
            </form>
        </div>
    </div>

    <div class="hm-card">
        @include('backend.partials.table-toolbar', [
            'placeholder' => 'Search anything a person typed',
            'statuses'    => collect($statuses)->mapWithKeys(fn ($s) => [$s => $s])->all(),
        ])
        <div class="table-responsive">
            <table class="hm-table">
                <thead>
                    <tr>
                        <th>ID</th><th>Form</th><th>Response</th><th>Submitted</th><th>Status</th><th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($responses as $response)
                        <tr>
                            <td>#{{ $response->id }}</td>
                            <td class="hm-table__name">
                                @if ($response->form)
                                    {{ $response->form->name }}
                                @else
                                    {{-- The form is gone but its responses were not
                                         taken with it. Say so, rather than showing a
                                         bare dash that reads like missing data. --}}
                                    <span class="hm-table__sub">form deleted</span>
                                @endif
                            </td>
                            <td>
                                {{-- Two forms here share no columns, so the cell
                                     shows the first answers as "question: answer"
                                     rather than pretending to a shared shape. --}}
                                @forelse ($response->values->take(3) as $value)
                                    <span class="hm-table__sub d-block">
                                        <strong>{{ \Illuminate\Support\Str::limit($value->field_label, 24) }}:</strong>
                                        {{ \Illuminate\Support\Str::limit($value->display, 44) ?: '—' }}
                                    </span>
                                @empty
                                    <span class="hm-table__sub">— submitted empty —</span>
                                @endforelse
                                @if ($response->values->count() > 3)
                                    <span class="hm-table__sub d-block">
                                        +{{ $response->values->count() - 3 }} more
                                    </span>
                                @endif
                            </td>
                            <td class="hm-table__date">
                                {{ ($response->submitted_at ?? $response->created_at)->format('d M Y') }}
                                <span class="hm-table__sub">{{ ($response->submitted_at ?? $response->created_at)->format('g:i a') }}</span>
                            </td>
                            <td>
                                @if ($response->form)
                                    <form method="POST" action="{{ route('backend.forms.responses.status', [$response->form, $response]) }}" class="d-inline">
                                        @csrf @method('PATCH')
                                        <select name="status" class="pill pill--tiny pill--{{ $response->status_slug }} hm-status-select"
                                                onchange="this.form.submit()" style="border:0;cursor:pointer;">
                                            @foreach ($statuses as $s)
                                                <option value="{{ $s }}" {{ $response->status === $s ? 'selected' : '' }}>{{ $s }}</option>
                                            @endforeach
                                        </select>
                                    </form>
                                @else
                                    {{-- Read-only once the form is gone. Where the
                                         foreign key is enforced MySQL refuses to
                                         update a row whose parent has vanished, and
                                         tracking a lead for a form that no longer
                                         exists means nothing anyway. It can still be
                                         deleted, which is all that is wanted here. --}}
                                    <span class="pill pill--tiny pill--{{ $response->status_slug }}">{{ $response->status }}</span>
                                @endif
                            </td>
                            <td class="text-end">
                                <div class="d-inline-flex gap-2">
                                    {{-- The detail page is built from the form's own
                                         questions, so it is the one thing an orphan
                                         cannot offer. The answers are all in the
                                         Response column either way. --}}
                                    @if ($response->form)
                                        <a href="{{ route('backend.forms.responses.show', [$response->form, $response]) }}"
                                           class="btn-ghost btn-icon" aria-label="View">
                                            <span class="act-ico act-ico--view" aria-hidden="true"></span>
                                        </a>
                                    @endif
                                    <form method="POST" action="{{ route('backend.forms.response-destroy', $response) }}"
                                          data-confirm="Delete this response? Any files uploaded with it are deleted too." class="d-inline">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="btn-danger-soft btn-icon" aria-label="Delete">
                                            <span class="act-ico act-ico--delete" aria-hidden="true"></span>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center py-5 hm-table__sub">
                                No responses yet. Share a form's link and everything people send
                                arrives here.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    @include('backend.partials.table-pagination', ['paginator' => $responses])

@endsection
