<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Http\Requests\Backend\EventRequest;
use App\Models\Event;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class EventController extends Controller
{
    public function index(): View
    {
        $events = Event::orderBy('sort_order')->orderBy('id')->get();

        return view('backend.events.index', compact('events'));
    }

    public function create(): View
    {
        return view('backend.events.form', [
            'event' => new Event(['is_active' => true, 'show_home' => true, 'tone' => 'purple', 'type' => 'Live Event']),
        ]);
    }

    public function store(EventRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $data['image'] = $this->storeImage($request);

        Event::create($data);

        return redirect()->route('backend.events.index')->with('success', 'Event added.');
    }

    public function edit(Event $event): View
    {
        return view('backend.events.form', compact('event'));
    }

    public function update(EventRequest $request, Event $event): RedirectResponse
    {
        $data = $request->validated();

        if ($request->hasFile('image')) {
            $this->deleteImage($event->image);
            $data['image'] = $this->storeImage($request);
        } else {
            unset($data['image']);   // keep the existing one
        }

        $event->update($data);

        return redirect()->route('backend.events.index')->with('success', 'Event updated.');
    }

    public function destroy(Event $event): RedirectResponse
    {
        $this->deleteImage($event->image);
        $event->delete();

        return redirect()->route('backend.events.index')->with('success', 'Event deleted.');
    }

    /** Store the uploaded image on the public disk; return a /public-relative path. */
    private function storeImage(EventRequest $request): string
    {
        $path = $request->file('image')->store('events', 'public');

        return 'storage/' . $path;
    }

    /** Delete a previously-uploaded image, but never the seeded asset files. */
    private function deleteImage(?string $image): void
    {
        if ($image && str_starts_with($image, 'storage/')) {
            Storage::disk('public')->delete(substr($image, strlen('storage/')));
        }
    }
}
