<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Backend\Concerns\OptimizesImageUploads;
use App\Http\Controllers\Controller;
use App\Http\Requests\Backend\AboutSectionRequest;
use App\Models\AboutSection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

/**
 * Sections → About Us.
 *
 * The four blocks are seeded and fixed — each has its own markup and its own
 * place on the page — so this is an overview plus an edit/update pair, the same
 * shape the Hero singleton uses. Nothing here creates or deletes a section.
 */
class AboutSectionController extends Controller
{
    use OptimizesImageUploads;

    public function index(): View
    {
        return view('backend.about-sections.index', [
            'sections' => $this->ordered(),
        ]);
    }

    public function edit(string $key): View
    {
        return view('backend.about-sections.edit', [
            'section' => $this->find($key),
        ]);
    }

    public function update(AboutSectionRequest $request, string $key): RedirectResponse
    {
        $section = $this->find($key);
        $data = $request->safe()->only(['label', 'title', 'lead', 'is_active']);

        // A section that shows no photo of its own must not quietly keep one.
        if ($section->usesImage()) {
            if ($request->hasFile('image')) {
                $this->deleteUpload($section->image);
                $data['image'] = $this->storeOptimizedImage($request->file('image'), 'about');
            } elseif ($request->boolean('remove_image')) {
                $this->deleteUpload($section->image);
                $data['image'] = null;
            }
        }

        $section->fill($data)->save();

        $this->syncItems($request, $section);

        return redirect()
            ->route('backend.about-sections.index')
            ->with('success', $section->name . ' updated.');
    }

    /**
     * Replace the section's rows.
     *
     * display_order comes from the submitted order rather than a number the
     * admin types: the form's ▲/▼ buttons move a row in the DOM and a form
     * always posts its fields in document order, so moving a row is all the
     * reordering there is.
     *
     * Colour and position come from that same order rather than from the form:
     * they are design decisions, so the panel does not ask for them. Row one
     * takes the first colour, row two the second, and the cycle repeats — which
     * reproduces the page as it was drawn and keeps it coherent however many
     * rows are added.
     *
     * A row's photo is carried across on the hidden `existing_image` input.
     * That input is posted by the browser, so it is matched against the images
     * this section actually owns before it is stored — otherwise an edited form
     * could point a row at any file under /storage. Images no longer claimed by
     * any row are deleted from disk rather than left orphaned.
     */
    private function syncItems(AboutSectionRequest $request, AboutSection $section): void
    {
        $known = $section->items()->pluck('image')->filter()->unique()->all();
        $kept = [];
        $created = [];
        $order = 0;

        foreach ($request->input('items', []) as $index => $row) {
            $title = trim($row['title'] ?? '');

            if ($title === '') {
                continue;
            }

            $image = null;

            if ($section->usesItemImages()) {
                if ($file = $request->file("items.{$index}.image")) {
                    $image = $this->storeOptimizedImage($file, 'about');
                } elseif (in_array($row['existing_image'] ?? null, $known, true)) {
                    $image = $row['existing_image'];
                    $kept[] = $image;
                }
            }

            $created[] = [
                'title'         => $title,
                'text'          => trim($row['text'] ?? '') ?: null,
                'image'         => $image,
                'tone'          => $section->toneFor($order),
                'position'      => $section->positionFor($order),
                'zoom'          => $section->usesItemImages() && (bool) ($row['zoom'] ?? false),
                'eyes'          => $section->key === 'features' && (bool) ($row['eyes'] ?? false),
                'display_order' => $order++,
                'is_active'     => (bool) ($row['is_active'] ?? false),
            ];
        }

        $section->items()->delete();

        foreach ($created as $attributes) {
            $section->items()->create($attributes);
        }

        foreach (array_diff($known, $kept) as $orphan) {
            $this->deleteUpload($orphan);
        }
    }

    /** The four sections in page order, rows counted for the overview. */
    private function ordered()
    {
        $order = array_keys(AboutSection::SECTIONS);

        return AboutSection::withCount('items')
            ->get()
            ->sortBy(fn ($section) => array_search($section->key, $order, true))
            ->values();
    }

    private function find(string $key): AboutSection
    {
        return AboutSection::where('key', $key)->firstOrFail();
    }

    /** Delete an image uploaded through the panel, never the seeded artwork. */
    private function deleteUpload(?string $path): void
    {
        if ($path && str_starts_with($path, 'storage/')) {
            Storage::disk('public')->delete(substr($path, strlen('storage/')));
        }
    }
}
