<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Http\Requests\Backend\PlacementSectionRequest;
use App\Models\ActivityLog;
use App\Models\PlacementSection;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

/**
 * Placement Readiness → the nine blocks of /placement-readiness.
 *
 * The blocks are seeded and fixed — each has its own markup and its own place
 * on the page — so this is an overview plus an edit/update pair, the same shape
 * About Us uses. Nothing here creates or deletes a block; everything that
 * repeats INSIDE one (challenges, stages, score bands, modules, steps, formats,
 * reasons) is a repeater row, added, reordered, hidden and removed on the edit
 * screen and written by syncItems().
 */
class PlacementSectionController extends Controller
{
    public function index(): View
    {
        return view('backend.placement.index', [
            'sections' => PlacementSection::inPageOrder(PlacementSection::query()->withCount('items')),
        ]);
    }

    public function edit(string $key): View
    {
        return view('backend.placement.edit', [
            'section' => $this->find($key),
        ]);
    }

    public function update(PlacementSectionRequest $request, string $key): RedirectResponse
    {
        $section = $this->find($key);

        // Only the fields this block actually draws — an edit form that does
        // not ask for a button must not be able to set one.
        $data = ['is_active' => $request->boolean('is_active')];

        foreach (['eyebrow', 'title', 'lead'] as $field) {
            $data[$field] = $request->input($field);
        }

        if ($section->uses('note')) {
            $data['note'] = $request->input('note');
        }

        if ($section->uses('buttons') || $section->uses('primary')) {
            $data['primary_label'] = $request->input('primary_label');
            $data['primary_url']   = $request->input('primary_url');
        }

        if ($section->uses('buttons')) {
            $data['secondary_label'] = $request->input('secondary_label');
            $data['secondary_url']   = $request->input('secondary_url');
        }

        if ($section->uses('contact')) {
            $data['phone'] = $request->input('phone');
            $data['email'] = $request->input('email');
        }

        $section->fill($data)->save();

        $this->syncItems($request, $section);

        ActivityLog::record('Placement Readiness Updated', "“{$section->name}” on the Placement Readiness page was updated");

        return redirect()
            ->route('backend.placement-readiness.index')
            ->with('success', $section->name . ' updated.');
    }

    /**
     * Replace one block's rows, list by list.
     *
     * display_order comes from the order the rows were posted in rather than a
     * number the admin types: the ▲ / ▼ buttons move a row in the DOM and a
     * form always posts its fields in document order, so moving a row is all
     * the reordering there is.
     *
     * Only the lists this block declares are touched — a stray `items[banana]`
     * in a hand-edited form writes nothing.
     */
    private function syncItems(PlacementSectionRequest $request, PlacementSection $section): void
    {
        $posted = (array) $request->input('items', []);

        foreach (array_keys($section->groups()) as $group) {
            $rows  = [];
            $order = 0;

            foreach ((array) ($posted[$group] ?? []) as $row) {
                $title = trim((string) ($row['title'] ?? ''));

                // A row an admin added and never filled in is not a row.
                if ($title === '') {
                    continue;
                }

                $rows[] = [
                    'group'         => $group,
                    'title'         => $title,
                    'subtitle'      => $section->groupUses($group, 'subtitle') ? (trim((string) ($row['subtitle'] ?? '')) ?: null) : null,
                    'text'          => $section->groupUses($group, 'text') ? (trim((string) ($row['text'] ?? '')) ?: null) : null,
                    'display_order' => $order++,
                    'is_active'     => (bool) ($row['is_active'] ?? false),
                ];
            }

            $section->items()->where('group', $group)->delete();

            foreach ($rows as $attributes) {
                $section->items()->create($attributes);
            }
        }
    }

    private function find(string $key): PlacementSection
    {
        abort_unless(array_key_exists($key, PlacementSection::SECTIONS), 404);

        return PlacementSection::where('key', $key)->firstOrFail();
    }
}
