<?php

use App\Models\Event;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Card colours are no longer chosen in the panel — the models hand them out in
 * palette order on create. The rows already in the database were coloured by
 * hand, so they carry whatever was picked at the time: neighbours repeat and the
 * grid reads as an accident rather than a set.
 *
 * This walks each table in the order the cards are actually rendered and deals
 * the palette out around it, so the existing pages pick up the same clean loop a
 * freshly-seeded install would get.
 */
return new class extends Migration
{
    public function up(): void
    {
        // Events only. A category's colour is no longer read from its row at all
        // — the home grid takes it from the card's position, because that grid
        // shows a filtered subset and a value dealt per row would fall out of
        // sequence as categories are toggled on and off it.
        //
        // An event is drawn on four surfaces (carousel, listing, its own hero,
        // the sidebar), so its colour has to be stored or it would change from
        // page to page. Dealt here in the order the carousel and the listing use.
        $this->deal(
            'events',
            Event::TONES,
            DB::table('events')->orderBy('sort_order')->orderBy('id')->pluck('id')
        );
    }

    /**
     * Nothing to restore: the previous values were hand-picked, one at a time,
     * and were never derivable from anything. Leaving them as dealt is harmless
     * — every colour in the column is a valid member of the palette either way.
     */
    public function down(): void
    {
        //
    }

    private function deal(string $table, array $tones, $ids): void
    {
        foreach ($ids as $i => $id) {
            DB::table($table)->where('id', $id)->update(['tone' => $tones[$i % count($tones)]]);
        }
    }
};
