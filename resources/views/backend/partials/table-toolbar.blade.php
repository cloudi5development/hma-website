{{-- Toolbar row at the top of an admin table: keyword search, optional status
     filter and the "Show N entries" length menu. Everything is a GET form, so
     the state lives in the query string and survives pagination.

     @param string $placeholder  search box placeholder
     @param bool   $status       show the Active/Hidden filter (default true)
     @param array  $statuses     custom [value => label] options for that filter --}}

@php
    $showStatus = $status ?? true;
    $statusOptions = $statuses ?? ['active' => 'Active', 'inactive' => 'Hidden'];
    $perPageOptions = config('admin.per_page_options', [10]);
    // Carry any other filters already in the URL (course id, date, …) so the
    // toolbar never silently drops them on submit.
    $carry = collect(request()->query())->except(['q', 'status', 'per_page', 'page']);
@endphp

<form method="GET" class="table-toolbar" id="tableToolbar">
    @foreach ($carry as $key => $value)
        @if (! is_array($value))
            <input type="hidden" name="{{ $key }}" value="{{ $value }}">
        @endif
    @endforeach

    <label class="table-toolbar__search">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/>
        </svg>
        <input type="search" name="q" value="{{ request('q') }}"
               placeholder="{{ $placeholder ?? 'Search…' }}" aria-label="Search">
    </label>

    @if ($showStatus)
        <select name="status" class="table-toolbar__select" aria-label="Filter by status" onchange="this.form.submit()">
            <option value="">All Status</option>
            @foreach ($statusOptions as $value => $label)
                <option value="{{ $value }}" {{ (string) request('status') === (string) $value ? 'selected' : '' }}>{{ $label }}</option>
            @endforeach
        </select>
    @endif

    <div class="table-toolbar__len">
        <span>Show</span>
        <select name="per_page" class="table-toolbar__select" aria-label="Entries per page" onchange="this.form.submit()">
            @foreach ($perPageOptions as $option)
                <option value="{{ $option }}" {{ (int) request('per_page', $perPageOptions[0]) === $option ? 'selected' : '' }}>{{ $option }}</option>
            @endforeach
        </select>
        <span>entries</span>
    </div>

    {{-- Submitting on Enter is enough; this keeps the form submittable without
         a visible button and lets browsers show the search affordance. --}}
    <button type="submit" class="visually-hidden">Search</button>
</form>
