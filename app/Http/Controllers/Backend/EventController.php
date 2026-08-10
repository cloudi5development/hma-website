<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Backend\Concerns\HandlesTableQuery;
use App\Http\Controllers\Backend\Concerns\OptimizesImageUploads;
use App\Http\Controllers\Controller;
use App\Http\Requests\Backend\EventRequest;
use App\Models\Event;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class EventController extends Controller
{
    use HandlesTableQuery;
    use OptimizesImageUploads;

    /** Rows kept per repeater. Matches the max: rule in EventRequest. */
    private const MAX_ROWS = 12;

    /**
     * The details-page images, and the longest edge each is stored at. They are
     * all optional: a blank one falls back to the speaker photo or the shared
     * events banner, so the page never renders a broken image.
     */
    private const OPTIONAL_IMAGES = [
        'banner_image' => ['events/banners', 1920],
        'thumbnail'    => ['events/thumbs', 600],
        'og_image'     => ['events/og', 1200],
    ];

    public function index(): View
    {
        $events = $this->applyTableFilters(Event::query(), ['speaker', 'title', 'type'])
            ->orderBy('sort_order')->orderBy('id')
            ->paginate($this->perPage())->withQueryString();

        return view('backend.events.index', compact('events'));
    }

    public function create(): View
    {
        return view('backend.events.form', [
            'event' => new Event(['is_active' => true, 'show_home' => true, 'tone' => 'purple', 'type' => Event::TYPES[0]]),
        ]);
    }

    public function store(EventRequest $request): RedirectResponse
    {
        $data = $this->clean($request);
        $data['image'] = $this->storeImage($request);

        foreach (self::OPTIONAL_IMAGES as $field => [$directory, $maxEdge]) {
            if ($request->hasFile($field)) {
                $data[$field] = $this->storeOptimizedImage($request->file($field), $directory, $maxEdge);
            }
        }

        $event = Event::create($data);
        $this->syncDetails($request, $event);

        return redirect()->route('backend.events.index')->with('success', 'Event added.');
    }

    public function edit(Event $event): View
    {
        $event->load(['highlights', 'speakers', 'faqs']);

        return view('backend.events.form', compact('event'));
    }

    public function update(EventRequest $request, Event $event): RedirectResponse
    {
        $data = $this->clean($request);

        if ($request->hasFile('image')) {
            $this->deleteUpload($event->image);
            $data['image'] = $this->storeImage($request);
        }

        // A new upload replaces the old file; ticking "remove" clears it. Doing
        // neither leaves the existing image alone.
        foreach (self::OPTIONAL_IMAGES as $field => [$directory, $maxEdge]) {
            if ($request->hasFile($field)) {
                $this->deleteUpload($event->{$field});
                $data[$field] = $this->storeOptimizedImage($request->file($field), $directory, $maxEdge);
            } elseif ($request->boolean('remove_' . $field)) {
                $this->deleteUpload($event->{$field});
                $data[$field] = null;
            }
        }

        $event->update($data);
        $this->syncDetails($request, $event);

        return redirect()->route('backend.events.index')->with('success', 'Event updated.');
    }

    public function destroy(Event $event): RedirectResponse
    {
        $this->deleteUpload($event->image);

        foreach (array_keys(self::OPTIONAL_IMAGES) as $field) {
            $this->deleteUpload($event->{$field});
        }

        foreach ($event->speakers()->pluck('photo') as $photo) {
            $this->deleteUpload($photo);
        }

        $event->delete();   // highlights / speakers / FAQs cascade via the FK

        return redirect()->route('backend.events.index')->with('success', 'Event deleted.');
    }

    /* ============================== REPEATERS ============================== */

    private function syncDetails(EventRequest $request, Event $event): void
    {
        $this->syncHighlights($event, $request->input('highlights', []));
        $this->syncSpeakers($request, $event);
        $this->syncFaqs($event, $request->input('event_faqs', []));
    }

    /**
     * Replace the "What You Will Learn" rows.
     *
     * display_order is taken from the submitted order rather than a number the
     * admin types: the form's up/down buttons move the row in the DOM, and a form
     * always posts its fields in document order, so dragging a row to the top is
     * all it takes to reorder it.
     */
    private function syncHighlights(Event $event, array $rows): void
    {
        $event->highlights()->delete();
        $order = 0;

        foreach ($this->limited($rows) as $row) {
            $title = trim($row['title'] ?? '');

            if ($title === '') {
                continue;
            }

            $event->highlights()->create([
                'icon'          => $row['icon'] ?? null,
                'title'         => $title,
                'description'   => trim($row['description'] ?? '') ?: null,
                'display_order' => $order++,
                'is_active'     => (bool) ($row['is_active'] ?? false),
            ]);
        }
    }

    /**
     * Replace the speaker rows, carrying each existing photo across on the hidden
     * `existing_photo` input.
     *
     * That input is posted by the browser, so it is matched against the photos
     * this event actually owns before it is stored — otherwise an edited form
     * could point a speaker at any file under /storage. Photos no longer claimed
     * by any row are deleted from disk rather than left orphaned.
     */
    private function syncSpeakers(EventRequest $request, Event $event): void
    {
        $known = $event->speakers()->pluck('photo')->filter()->unique()->all();
        $kept = [];
        $created = [];
        $order = 0;

        foreach ($this->limited($request->input('speakers', [])) as $index => $row) {
            $name = trim($row['name'] ?? '');

            if ($name === '') {
                continue;
            }

            $photo = null;

            if ($file = $request->file("speakers.{$index}.photo")) {
                $photo = $this->storeOptimizedImage($file, 'events/speakers', 600);
            } elseif (in_array($row['existing_photo'] ?? null, $known, true)) {
                $photo = $row['existing_photo'];
                $kept[] = $photo;
            }

            $created[] = [
                'name'          => $name,
                'designation'   => trim($row['designation'] ?? '') ?: null,
                'company'       => trim($row['company'] ?? '') ?: null,
                'photo'         => $photo,
                'linkedin'      => trim($row['linkedin'] ?? '') ?: null,
                'display_order' => $order++,
                'is_active'     => (bool) ($row['is_active'] ?? false),
            ];
        }

        $event->speakers()->delete();

        foreach ($created as $attributes) {
            $event->speakers()->create($attributes);
        }

        foreach (array_diff($known, $kept) as $orphan) {
            $this->deleteUpload($orphan);
        }
    }

    /** Replace the event's FAQ rows. A row needs both a question and an answer. */
    private function syncFaqs(Event $event, array $rows): void
    {
        $event->faqs()->delete();
        $order = 0;

        foreach ($this->limited($rows) as $row) {
            $question = trim($row['question'] ?? '');
            $answer = trim($row['answer'] ?? '');

            if ($question === '' || $answer === '') {
                continue;
            }

            $event->faqs()->create([
                'question'      => $question,
                'answer'        => $answer,
                'display_order' => $order++,
                'is_active'     => (bool) ($row['is_active'] ?? false),
            ]);
        }
    }

    /**
     * The first MAX_ROWS rows with their keys intact — the keys are what pairs a
     * speaker row with its uploaded photo, so array_slice() preserves them.
     */
    private function limited(mixed $rows): array
    {
        return is_array($rows) ? array_slice($rows, 0, self::MAX_ROWS, true) : [];
    }

    /* =============================== HELPERS =============================== */

    /**
     * Validated payload minus the fields handled separately: the three repeaters
     * and the file inputs, whose column values are computed rather than posted.
     */
    private function clean(EventRequest $request): array
    {
        return collect($request->validated())
            ->except(['highlights', 'speakers', 'event_faqs', 'image', ...array_keys(self::OPTIONAL_IMAGES)])
            ->all();
    }

    /** Store the uploaded speaker cut-out; return a /public-relative path. */
    private function storeImage(EventRequest $request): string
    {
        return $this->storeOptimizedImage($request->file('image'), 'events');
    }

    /** Remove a previously-uploaded file, but never the seeded asset files. */
    private function deleteUpload(?string $path): void
    {
        if ($path && str_starts_with($path, 'storage/')) {
            Storage::disk('public')->delete(substr($path, strlen('storage/')));
        }
    }
}
