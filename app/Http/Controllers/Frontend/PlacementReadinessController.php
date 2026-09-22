<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\PlacementSection;
use Illuminate\View\View;

/**
 * /placement-readiness — the Placement Success Program page.
 *
 * Nothing about the page is written here or in its view: the nine blocks and
 * every row inside them come from the panel (Admin → Placement Readiness). A
 * block switched off, or one whose rows are all hidden, simply is not drawn.
 */
class PlacementReadinessController extends Controller
{
    public function index(): View
    {
        // Active blocks, each with only the rows that are themselves active —
        // one query for the blocks and one for the rows, whatever the page grows to.
        $sections = PlacementSection::inPageOrder(
            PlacementSection::query()
                ->active()
                ->with(['items' => fn ($query) => $query->where('is_active', true)])
        )->keyBy('key');

        return view('frontend.placement-readiness', ['sections' => $sections]);
    }
}
