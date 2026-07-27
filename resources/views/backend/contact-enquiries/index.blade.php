@extends('backend.template.layouts.template-base')

@section('title', 'Contact Enquiries')
@section('page_title', 'Contact Enquiries')
@section('page_sub', 'Submissions from the website contact form')

@section('content')

    <div class="page-head">
        <div>
            <h1 class="page-head__title">Contact Enquiries</h1>
            <p class="page-head__sub">{{ $enquiries->total() }} enquir{{ $enquiries->total() === 1 ? 'y' : 'ies' }}</p>
        </div>
        <div class="d-inline-flex gap-2">
            <a href="{{ route('backend.contact-enquiries.export-excel', request()->query()) }}" class="btn-brand">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3v12m0 0 4-4m-4 4-4-4M4 17v2a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-2"/></svg>
                Export Excel
            </a>
            <a href="{{ route('backend.contact-enquiries.export', request()->query()) }}" class="btn-ghost">Export CSV</a>
        </div>
    </div>

    @if (session('success'))
        <div class="alert-hm alert-hm--success">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg>
            {{ session('success') }}
        </div>
    @endif

    {{-- Filters --}}
    <div class="hm-card mb-3">
        <div class="hm-card__body">
            <form method="GET" class="row g-2 align-items-end">
                <div class="col-12 col-md-4">
                    <label class="form-label" for="q">Search</label>
                    <input type="text" id="q" name="q" value="{{ request('q') }}" class="form-control-hm" placeholder="Name, email or mobile">
                </div>
                <div class="col-6 col-md-3">
                    <label class="form-label" for="status">Status</label>
                    <select id="status" name="status" class="form-control-hm">
                        <option value="">All</option>
                        @foreach ($statuses as $s)
                            <option value="{{ $s }}" {{ request('status') === $s ? 'selected' : '' }}>{{ $s }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-6 col-md-3">
                    <label class="form-label" for="date">Date</label>
                    <input type="date" id="date" name="date" value="{{ request('date') }}" class="form-control-hm">
                </div>
                <div class="col-12 col-md-2 d-flex gap-2">
                    <button type="submit" class="btn-brand" style="flex:1;justify-content:center">Filter</button>
                    <a href="{{ route('backend.contact-enquiries.index') }}" class="btn-ghost">Clear</a>
                </div>
            </form>
        </div>
    </div>

    <div class="hm-card">
        <div class="table-responsive">
            <table class="hm-table">
                <thead>
                    <tr>
                        <th>ID</th><th>Name</th><th>Email</th><th>Mobile</th><th>Subject</th><th>Date</th><th>Status</th><th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($enquiries as $enquiry)
                        <tr>
                            <td>#{{ $enquiry->id }}</td>
                            <td class="hm-table__name">{{ $enquiry->name }}</td>
                            <td><a href="mailto:{{ $enquiry->email }}" class="hm-table__sub">{{ $enquiry->email }}</a></td>
                            <td>{{ $enquiry->phone }}</td>
                            <td>{{ $enquiry->subject ?: $enquiry->looking_for }}</td>
                            <td>{{ $enquiry->created_at->format('d M Y, g:i a') }}</td>
                            <td>
                                <form method="POST" action="{{ route('backend.contact-enquiries.status', $enquiry) }}" class="d-inline">
                                    @csrf @method('PATCH')
                                    <select name="status" class="pill pill--tiny pill--{{ strtolower($enquiry->status) }}" onchange="this.form.submit()"
                                            style="border:0;cursor:pointer;">
                                        @foreach ($statuses as $s)
                                            <option value="{{ $s }}" {{ $enquiry->status === $s ? 'selected' : '' }}>{{ $s }}</option>
                                        @endforeach
                                    </select>
                                </form>
                            </td>
                            <td class="text-end">
                                <div class="d-inline-flex gap-2">
                                    <a href="{{ route('backend.contact-enquiries.show', $enquiry) }}" class="btn-ghost btn-icon" aria-label="View">
                                        <span class="act-ico act-ico--view" aria-hidden="true"></span>
                                    </a>
                                    <form method="POST" action="{{ route('backend.contact-enquiries.destroy', $enquiry) }}"
                                          onsubmit="return confirm('Delete this enquiry?');" class="d-inline">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="btn-danger-soft btn-icon" aria-label="Delete">
                                            <span class="act-ico act-ico--delete" aria-hidden="true"></span>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="8" class="text-center py-5 hm-table__sub">No contact enquiries found.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    @include('backend.partials.table-pagination', ['paginator' => $enquiries])

@endsection
