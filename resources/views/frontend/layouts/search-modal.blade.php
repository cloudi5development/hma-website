{{-- Global search modal (Bootstrap 5). Triggered from the header search button. --}}
<div class="modal fade" id="searchModal" tabindex="-1" aria-labelledby="searchModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="searchModalLabel">Search</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form action="#" method="GET" role="search">
                    <input type="search" name="q" class="form-control" placeholder="Type to search…" aria-label="Search">
                </form>
            </div>
        </div>
    </div>
</div>
