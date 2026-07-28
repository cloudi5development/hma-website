<?php

namespace App\Http\Controllers\Backend;

use App\Exports\ArrayExport;
use App\Http\Controllers\Backend\Concerns\HandlesTableQuery;
use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\ContactEnquiry;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ContactEnquiryController extends Controller
{
    use HandlesTableQuery;

    public function index(Request $request): View
    {
        $enquiries = $this->filtered($request)->paginate($this->perPage())->withQueryString();

        return view('backend.contact-enquiries.index', [
            'enquiries' => $enquiries,
            'statuses'  => ContactEnquiry::STATUSES,
        ]);
    }

    public function show(ContactEnquiry $contactEnquiry): View
    {
        // Opening an enquiry marks it read.
        if (! $contactEnquiry->is_read) {
            $contactEnquiry->update(['is_read' => true]);
        }

        ActivityLog::record('Enquiry Viewed', "Viewed contact enquiry #{$contactEnquiry->id} from {$contactEnquiry->name}");

        return view('backend.contact-enquiries.show', ['enquiry' => $contactEnquiry]);
    }

    public function updateStatus(Request $request, ContactEnquiry $contactEnquiry): RedirectResponse
    {
        Validator::make($request->all(), [
            'status' => ['required', 'in:' . implode(',', ContactEnquiry::STATUSES)],
        ])->validate();

        $contactEnquiry->update(['status' => $request->input('status'), 'is_read' => true]);

        ActivityLog::record('Status Updated', "Contact enquiry #{$contactEnquiry->id} from {$contactEnquiry->name} → {$request->input('status')}");

        return back()->with('success', 'Status updated to ' . $request->input('status') . '.');
    }

    public function destroy(ContactEnquiry $contactEnquiry): RedirectResponse
    {
        ActivityLog::record('Enquiry Deleted', "Contact enquiry #{$contactEnquiry->id} from {$contactEnquiry->name} deleted");

        $contactEnquiry->delete();

        return redirect()->route('backend.contact-enquiries.index')->with('success', 'Enquiry deleted.');
    }

    /** Stream the (filtered) enquiries as a CSV. */
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
        }, 'contact-enquiries-' . now()->format('Y-m-d-His') . '.csv', ['Content-Type' => 'text/csv']);
    }

    /** Download the (filtered) enquiries as a real .xlsx workbook. */
    public function exportExcel(Request $request): BinaryFileResponse
    {
        [$headings, $rows] = $this->exportData($request);

        return Excel::download(new ArrayExport($headings, $rows), 'contact-enquiries-' . now()->format('Y-m-d-His') . '.xlsx');
    }

    /** Column headings + row arrays for the current filter — shared by CSV + Excel. */
    private function exportData(Request $request): array
    {
        $headings = ['ID', 'Name', 'Email', 'Mobile', 'Subject', 'Interest', 'Message', 'Status', 'IP', 'Date'];

        $rows = $this->filtered($request)->get()->map(fn ($r) => [
            $r->id, $r->name, $r->email, $r->phone, $r->subject ?: $r->looking_for,
            $r->interest, $r->message, $r->status, $r->ip_address,
            $r->created_at?->format('Y-m-d H:i'),
        ])->all();

        return [$headings, $rows];
    }

    /** Shared filter pipeline for the list + the export (search / status / date). */
    private function filtered(Request $request): Builder
    {
        return ContactEnquiry::query()
            ->latest()
            ->search($request->input('q'))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->input('status')))
            ->when($request->filled('date'), fn ($q) => $q->whereDate('created_at', $request->input('date')));
    }
}
