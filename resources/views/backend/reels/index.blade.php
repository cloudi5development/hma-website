@extends('backend.template.layouts.template-base')

@section('title', 'Our Journey Reels')
@section('page_title', 'Our Journey Reels')
@section('page_sub', 'Instagram reels shown on the home “Our Journey” and testimonials “Career Success” sliders')

@section('content')

    <div class="page-head">
        <div>
            <h1 class="page-head__title">Our Journey Reels</h1>
            <p class="page-head__sub">{{ $reels->total() }} reel{{ $reels->total() === 1 ? '' : 's' }} · shown on the home & testimonials pages</p>
        </div>
        <a href="{{ route('backend.reels.create') }}" class="btn-brand">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round"><path d="M12 5v14M5 12h14"/></svg>
            Add Reel
        </a>
    </div>


    <div class="hm-card">
        @include("backend.partials.table-toolbar", ["placeholder" => "Search reel title"])
        <div class="table-responsive">
            <table class="hm-table">
                <thead>
                    <tr>
                        <th>Video</th><th>Title</th><th>Plays from</th><th>Instagram Link</th><th>Status</th><th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($reels as $reel)
                        <tr>
                            <td>
                                <span class="tbl-logo" style="background:#000">
                                    @if ($reel->video_url)
                                        <video src="{{ $reel->video_url }}" muted playsinline style="width:100%;height:100%;object-fit:cover"></video>
                                    @else
                                        {{-- Link-only: nothing of ours to show a frame of. --}}
                                        <span style="display:flex;align-items:center;justify-content:center;width:100%;height:100%;color:#E1306C;font-size:20px">
                                            <i class="fa-brands fa-instagram" aria-hidden="true"></i>
                                        </span>
                                    @endif
                                </span>
                            </td>
                            <td class="hm-table__name">{{ $reel->title }}</td>
                            {{-- The two kinds behave differently on the site, so the
                                 listing says which is which rather than leaving it to
                                 be worked out from the other columns. --}}
                            <td>
                                @if ($reel->usesEmbed())
                                    <span class="pill pill--tiny">Instagram</span>
                                    <br><span class="hm-table__sub">Click to play</span>
                                @else
                                    <span class="pill pill--tiny pill--active">Upload</span>
                                    <br><span class="hm-table__sub">Autoplays</span>
                                @endif
                            </td>
                            <td>
                                @if ($reel->instagram_url)
                                    <a href="{{ $reel->instagram_url }}" target="_blank" rel="noopener" class="hm-table__sub">{{ \Illuminate\Support\Str::limit($reel->instagram_url, 40) }}</a>
                                @else
                                    <span class="hm-table__sub">—</span>
                                @endif
                            </td>
                            <td>
                                <span class="pill pill--tiny {{ $reel->is_active ? 'pill--active' : 'pill--inactive' }}">
                                    {{ $reel->is_active ? 'Active' : 'Hidden' }}
                                </span>
                            </td>
                            <td class="text-end">
                                <div class="d-inline-flex gap-2">
                                    <a href="{{ route('backend.reels.edit', $reel) }}" class="btn-ghost btn-icon" aria-label="Edit">
                                        <span class="act-ico act-ico--edit" aria-hidden="true"></span>
                                    </a>
                                    <form method="POST" action="{{ route('backend.reels.destroy', $reel) }}"
                                          data-confirm="Delete this reel?" class="d-inline">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="btn-danger-soft btn-icon" aria-label="Delete">
                                            <span class="act-ico act-ico--delete" aria-hidden="true"></span>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="text-center py-5 hm-table__sub">No reels yet. Add your first one.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    @include('backend.partials.table-pagination', ['paginator' => $reels])

@endsection
