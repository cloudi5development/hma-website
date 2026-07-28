@extends('backend.template.layouts.template-base')

@section('title', 'Success Stories')
@section('page_title', 'Success Stories')
@section('page_sub', 'Learner career stories shown in the grid on the home page')

@section('content')

    <div class="page-head">
        <div>
            <h1 class="page-head__title">Success Stories</h1>
            <p class="page-head__sub">{{ $stories->total() }} stor{{ $stories->total() === 1 ? 'y' : 'ies' }}</p>
        </div>
        <a href="{{ route('backend.success-stories.create') }}" class="btn-brand">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round"><path d="M12 5v14M5 12h14"/></svg>
            Add Story
        </a>
    </div>


    <div class="hm-card">
        @include("backend.partials.table-toolbar", ["placeholder" => "Search student name"])
        <div class="table-responsive">
            <table class="hm-table">
                <thead>
                    <tr>
                        <th>Photo</th><th>Name</th><th>Role</th><th>Package</th><th>Tone</th><th>Status</th><th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($stories as $story)
                        <tr>
                            <td><span class="tbl-logo tbl-logo--round"><img src="{{ $story->image_url }}" alt="{{ $story->name }}"></span></td>
                            <td class="hm-table__name">{{ $story->name }}</td>
                            <td>{{ $story->role }}</td>
                            <td>₹{{ $story->salary }} LPA</td>
                            <td><span class="pill pill--tiny pill--interested">{{ ucfirst($story->tone) }}</span></td>
                            <td>
                                <span class="pill pill--tiny {{ $story->is_active ? 'pill--active' : 'pill--inactive' }}">
                                    {{ $story->is_active ? 'Active' : 'Hidden' }}
                                </span>
                            </td>
                            <td class="text-end">
                                <div class="d-inline-flex gap-2">
                                    <a href="{{ route('backend.success-stories.edit', $story) }}" class="btn-ghost btn-icon" aria-label="Edit">
                                        <span class="act-ico act-ico--edit" aria-hidden="true"></span>
                                    </a>
                                    <form method="POST" action="{{ route('backend.success-stories.destroy', $story) }}"
                                          data-confirm="Delete this success story?" class="d-inline">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="btn-danger-soft btn-icon" aria-label="Delete">
                                            <span class="act-ico act-ico--delete" aria-hidden="true"></span>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="text-center py-5 hm-table__sub">No success stories yet. Add your first one.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    @include('backend.partials.table-pagination', ['paginator' => $stories])

@endsection
