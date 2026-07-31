<?php

namespace App\Http\Controllers\Backend;

use App\Exports\ArrayExport;
use App\Http\Controllers\Backend\Concerns\HandlesTableQuery;
use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Event;
use App\Models\EventRegistration;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * Leads → Event Registration. Mirrors CourseEnquiryController so the three Leads
 * lists share one set of habits: same filters, same status workflow, same
 * exports.
 */
class EventRegistrationController extends Controller
{
    use HandlesTableQuery;

    public function index(Request $request): View
    {
        $registrations = $this->filtered($request)->with('event')->paginate($this->perPage())->withQueryString();

        // When arriving from an event's "View Registrations" button, name the event.
        $event = $request->filled('event') ? Event::find($request->integer('event')) : null;

        return view('backend.event-registrations.index', [
            'registrations' => $registrations,
            'statuses'      => EventRegistration::STATUSES,
            'event'         => $event,
        ]);
    }

    public function show(EventRegistration $eventRegistration): View
    {
        ActivityLog::record('Registration Viewed', "Viewed event registration #{$eventRegistration->id} from {$eventRegistration->name}");

        return view('backend.event-registrations.show', ['registration' => $eventRegistration->load('event')]);
    }

    public function updateStatus(Request $request, EventRegistration $eventRegistration): RedirectResponse
    {
        Validator::make($request->all(), [
            'status' => ['required', 'in:' . implode(',', EventRegistration::STATUSES)],
        ])->validate();

        $eventRegistration->update(['status' => $request->input('status')]);

        ActivityLog::record('Status Updated', "Event registration #{$eventRegistration->id} from {$eventRegistration->name} → {$request->input('status')}");

        return back()->with('success', 'Status updated to ' . $request->input('status') . '.');
    }

    public function destroy(EventRegistration $eventRegistration): RedirectResponse
    {
        ActivityLog::record('Registration Deleted', "Event registration #{$eventRegistration->id} from {$eventRegistration->name} deleted");

        $eventRegistration->delete();

        return redirect()->route('backend.event-registrations.index')->with('success', 'Registration deleted.');
    }

    /** Stream the (filtered) registrations as a CSV. */
    public function export(Request $request): StreamedResponse
    {
        [$headings, $rows] = $this->exportData($request);

        return response()->streamDownload(function () use ($headings, $rows) {
            $out = fopen('php://output', 'w');
            fputcsv($out, $headings);
            foreach ($rows as $r) {
                fputcsv($out, $r);
            }
            fclose($out);
        }, 'event-registrations-' . now()->format('Y-m-d-His') . '.csv', ['Content-Type' => 'text/csv']);
    }

    /** Download the (filtered) registrations as a real .xlsx workbook. */
    public function exportExcel(Request $request): BinaryFileResponse
    {
        [$headings, $rows] = $this->exportData($request);

        return Excel::download(new ArrayExport($headings, $rows), 'event-registrations-' . now()->format('Y-m-d-His') . '.xlsx');
    }

    /** Column headings + row arrays for the current filter — shared by CSV + Excel. */
    private function exportData(Request $request): array
    {
        $headings = ['ID', 'Event', 'Name', 'Email', 'Mobile', 'City', 'Professional Status', 'Company / College', 'Status', 'IP', 'Date'];

        $rows = $this->filtered($request)->with('event')->get()->map(fn ($r) => [
            $r->id, $r->event_title, $r->name, $r->email, $r->phone, $r->city,
            $r->professional_status, $r->organisation, $r->status, $r->ip_address,
            $r->created_at?->format('Y-m-d H:i'),
        ])->all();

        return [$headings, $rows];
    }

    /** Shared filter pipeline for the list + the export (search / status / date / event). */
    private function filtered(Request $request): Builder
    {
        return EventRegistration::query()
            ->latest()
            ->search($request->input('q'))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->input('status')))
            ->when($request->filled('event'), fn ($q) => $q->where('event_id', $request->integer('event')))
            ->when($request->filled('date'), fn ($q) => $q->whereDate('created_at', $request->input('date')));
    }
}
