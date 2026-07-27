@extends('backend.template.layouts.template-base')

@section('title', 'Departments')
@section('page_title', 'Departments')
@section('page_sub', 'Domains at the top of the course hierarchy (mega-menu columns)')

@section('content')

    <div class="page-head">
        <div>
            <h1 class="page-head__title">Departments</h1>
            <p class="page-head__sub">{{ $departments->total() }} department{{ $departments->total() === 1 ? '' : 's' }}</p>
        </div>
        <a href="{{ route('backend.departments.create') }}" class="btn-brand">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round"><path d="M12 5v14M5 12h14"/></svg>
            Add Department
        </a>
    </div>

    @include('backend.partials.flash')

    <div class="hm-card">
        <div class="table-responsive">
            <table class="hm-table">
                <thead>
                    <tr>
                        <th>Department</th><th>Categories</th><th>Order</th><th>Status</th><th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($departments as $department)
                        <tr>
                            <td class="hm-table__name">{{ $department->name }}</td>
                            <td>{{ $department->categories_count }}</td>
                            <td>{{ $department->sort_order }}</td>
                            <td>
                                <span class="pill pill--tiny {{ $department->is_active ? 'pill--active' : 'pill--inactive' }}">
                                    {{ $department->is_active ? 'Active' : 'Hidden' }}
                                </span>
                            </td>
                            <td class="text-end">
                                <div class="d-inline-flex gap-2">
                                    <a href="{{ route('backend.departments.edit', $department) }}" class="btn-ghost btn-icon" aria-label="Edit">
                                        <span class="act-ico act-ico--edit" aria-hidden="true"></span>
                                    </a>
                                    <form method="POST" action="{{ route('backend.departments.destroy', $department) }}"
                                          onsubmit="return confirm('Delete this department?');" class="d-inline">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="btn-danger-soft btn-icon" aria-label="Delete">
                                            <span class="act-ico act-ico--delete" aria-hidden="true"></span>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="text-center py-5 hm-table__sub">No departments yet. Add your first one.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    @include('backend.partials.table-pagination', ['paginator' => $departments])

@endsection
