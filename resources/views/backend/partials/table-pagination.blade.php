{{-- Reusable admin table footer: page buttons pinned under the table.
     Pass the paginator:  @include('backend.partials.table-pagination', ['paginator' => $rows]) --}}
@if ($paginator instanceof \Illuminate\Contracts\Pagination\Paginator && $paginator->hasPages())
    <div class="hm-tablefoot">
        {{ $paginator->links() }}
    </div>
@endif
