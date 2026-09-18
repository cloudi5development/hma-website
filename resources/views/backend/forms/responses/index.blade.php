@extends('backend.template.layouts.template-base')

@section('title', 'Responses — ' . $form->name)
@section('page_title', 'Responses')
@section('page_sub', $form->name)

@section('content')

    <div class="page-head">
        <div>
            <h1 class="page-head__title">{{ $form->title }}</h1>
            <p class="page-head__sub">
                {{ $responses->total() }} response{{ $responses->total() === 1 ? '' : 's' }}
            </p>
        </div>
        <div class="d-inline-flex gap-2 flex-wrap">
            <a href="{{ route('backend.forms.responses.export-excel', [$form] + request()->query()) }}" class="btn-brand">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3v12m0 0 4-4m-4 4-4-4M4 17v2a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-2"/></svg>
                Export Excel
            </a>
            <a href="{{ route('backend.forms.responses.export', [$form] + request()->query()) }}" class="btn-ghost">Export CSV</a>

            {{-- Clears exactly what the filters above are showing, so it can
                 never take more than is on screen. The count and whether a
                 filter is on are both spelled out in the confirmation. --}}
            @if ($responses->total())
                {{-- filled(), not has(): the toolbar always submits q= and status=,
                     empty, and "Delete Filtered" must not claim a filter that
                     is not there. --}}
                @php $filtered = collect(['q', 'status', 'date'])->contains(fn ($k) => filled(request($k))); @endphp
                <form method="POST" action="{{ route('backend.forms.responses.clear', [$form] + request()->query()) }}"
                      class="d-inline"
                      data-confirm="Delete {{ $filtered ? 'the ' . number_format($responses->total()) . ' response(s) these filters are showing' : 'all ' . number_format($responses->total()) . ' response(s) of this form' }}, including any uploaded files? This cannot be undone."
                      data-confirm-title="Clear responses?"
                      data-confirm-label="Delete {{ number_format($responses->total()) }}">
                    @csrf @method('DELETE')
                    <button type="submit" class="btn-danger-soft">
                        {{ $filtered ? 'Delete Filtered' : 'Clear All' }}
                    </button>
                </form>
            @endif

            <a href="{{ route('backend.forms.show', $form) }}" class="btn-ghost">Back to Form</a>
        </div>
    </div>

    {{-- Date filter, matching the enquiry screens. Search and status live in the
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
                    <label class="form-label" for="date">Date</label>
                    <input type="date" id="date" name="date" value="{{ request('date') }}" class="form-control-hm">
                </div>
                <div class="col-12 col-md-3 d-flex gap-2">
                    <button type="submit" class="btn-brand" style="flex:1;justify-content:center">Filter</button>
                    <a href="{{ route('backend.forms.responses.index', $form) }}" class="btn-ghost">Clear</a>
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
                        <th>ID</th>
                        {{-- The columns ARE the form's own questions. A job
                             application shows Applicant / Resume / Experience and
                             a course enquiry shows Student Name / Mobile / Course,
                             with nothing here knowing either. --}}
                        @foreach ($columns as $column)
                            <th>{{ \Illuminate\Support\Str::limit($column->label, 28) }}</th>
                        @endforeach
                        <th>Submitted</th><th>Status</th><th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($responses as $response)
                        <tr>
                            <td>#{{ $response->id }}</td>
                            @foreach ($columns as $column)
                                <td @if ($loop->first) class="hm-table__name" @endif>
                                    {{-- Escaped by Blade, as everything a stranger
                                         typed must be. --}}
                                    {{ \Illuminate\Support\Str::limit($response->answerFor($column)?->display ?? '', 60) ?: '—' }}
                                </td>
                            @endforeach
                            <td class="hm-table__date">
                                {{ ($response->submitted_at ?? $response->created_at)->format('d M Y') }}
                                <span class="hm-table__sub">{{ ($response->submitted_at ?? $response->created_at)->format('g:i a') }}</span>
                            </td>
                            <td>
                                <form method="POST" action="{{ route('backend.forms.responses.status', [$form, $response]) }}" class="d-inline">
                                    @csrf @method('PATCH')
                                    <select name="status" class="pill pill--tiny pill--{{ $response->status_slug }} hm-status-select"
                                            onchange="this.form.submit()" style="border:0;cursor:pointer;">
                                        @foreach ($statuses as $s)
                                            <option value="{{ $s }}" {{ $response->status === $s ? 'selected' : '' }}>{{ $s }}</option>
                                        @endforeach
                                    </select>
                                </form>
                            </td>
                            <td class="text-end">
                                <div class="d-inline-flex gap-2">
                                    <a href="{{ route('backend.forms.responses.show', [$form, $response]) }}"
                                       class="btn-ghost btn-icon" aria-label="View">
                                        <span class="act-ico act-ico--view" aria-hidden="true"></span>
                                    </a>
                                    <form method="POST" action="{{ route('backend.forms.responses.destroy', [$form, $response]) }}"
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
                            <td colspan="{{ $columns->count() + 4 }}" class="text-center py-5 hm-table__sub">
                                @if ($form->isPublished())
                                    No responses yet. Share the form's link and they will appear here.
                                @else
                                    No responses yet — this form is {{ strtolower($form->status_label) }}, so it is not
                                    accepting any. <a href="{{ route('backend.forms.show', $form) }}">Publish it</a> to start.
                                @endif
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    @if ($form->fields->count() > $columns->count())
        <p class="form-hint mt-2">
            Showing the first {{ $columns->count() }} of this form's {{ $form->fields->count() }} fields.
            Open a response to see all of it, or export for the complete set of columns.
        </p>
    @endif

    @include('backend.partials.table-pagination', ['paginator' => $responses])

@endsection
