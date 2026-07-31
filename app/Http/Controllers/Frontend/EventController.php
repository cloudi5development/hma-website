<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\Event;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Public events: the listing at /events and one event at /events/{slug}.
 *
 * Split out of HomeController once the details page arrived — the two share the
 * same model and the listing's "Event Details" button links straight into show().
 */
class EventController extends Controller
{
    /** How many events a listing page holds, matching the design's grid. */
    private const PER_PAGE = 8;

    /** Events shown in the details sidebar under "Upcoming Events". */
    private const SIDEBAR_LIMIT = 3;

    /**
     * Every upcoming event, paginated — reached from the "See all" button under
     * the home page carousel. The search box filters on speaker and title.
     *
     * Unlike the home carousel this is not limited to events flagged for the home
     * page: the listing is the full programme.
     */
    public function index(Request $request): View
    {
        $search = trim((string) $request->query('q'));

        $events = Event::active()
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('title', 'like', "%{$search}%")
                      ->orWhere('speaker', 'like', "%{$search}%");
                });
            })
            ->paginate(self::PER_PAGE)
            ->withQueryString();

        return view('frontend.events', compact('events', 'search'));
    }

    /**
     * One event. 404 when the slug is unknown or the event is switched off.
     *
     * The three repeaters are eager-loaded in one go, so the page issues a fixed
     * number of queries no matter how many highlights/speakers/FAQs an event has.
     */
    public function show(string $slug): View
    {
        $event = Event::active()
            ->with([
                'highlights' => fn ($q) => $q->where('is_active', true),
                'speakers'   => fn ($q) => $q->where('is_active', true),
                'faqs'       => fn ($q) => $q->where('is_active', true),
            ])
            ->where('slug', $slug)
            ->firstOrFail();

        $upcoming = Event::upcomingBesides($event, self::SIDEBAR_LIMIT)->get();

        // The registration modal's "Select Event" dropdown: every published event,
        // so a visitor can switch without leaving the page. id + title only —
        // nothing else is rendered, and this list is unbounded.
        $eventOptions = Event::active()->orderBy('event_date')->orderBy('title')->get(['id', 'title']);

        return view('frontend.events.details', compact('event', 'upcoming', 'eventOptions'));
    }
}
