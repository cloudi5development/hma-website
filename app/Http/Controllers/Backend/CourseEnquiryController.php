<?php

namespace App\Http\Controllers\Backend;

use App\Exports\ArrayExport;
use App\Http\Controllers\Backend\Concerns\HandlesTableQuery;
use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Course;
use App\Models\CourseEnquiry;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CourseEnquiryController extends Controller
{
    use HandlesTableQuery;

    public function index(Request $request): View
    {
        $enquiries = $this->filtered($request)->with('course')->paginate($this->perPage())->withQueryString();

        // When arriving from a course's "View Enquiries" button, name the course.
        $course = $request->filled('course') ? Course::find($request->integer('course')) : null;

        return view('backend.course-enquiries.index', [
            'enquiries' => $enquiries,
            'statuses'  => CourseEnquiry::STATUSES,
            'course'    => $course,
        ]);
    }

    public function show(CourseEnquiry $courseEnquiry): View
    {
        ActivityLog::record('Enquiry Viewed', "Viewed course enquiry #{$courseEnquiry->id} from {$courseEnquiry->name}");

        return view('backend.course-enquiries.show', ['enquiry' => $courseEnquiry->load('course')]);
    }

    public function updateStatus(Request $request, CourseEnquiry $courseEnquiry): RedirectResponse
    {
        Validator::make($request->all(), [
            'status' => ['required', 'in:' . implode(',', CourseEnquiry::STATUSES)],
        ])->validate();

        $courseEnquiry->update(['status' => $request->input('status')]);

        ActivityLog::record('Status Updated', "Course enquiry #{$courseEnquiry->id} from {$courseEnquiry->name} → {$request->input('status')}");

        return back()->with('success', 'Status updated to ' . $request->input('status') . '.');
    }

    public function destroy(CourseEnquiry $courseEnquiry): RedirectResponse
    {
        ActivityLog::record('Enquiry Deleted', "Course enquiry #{$courseEnquiry->id} from {$courseEnquiry->name} deleted");

        $courseEnquiry->delete();

        return redirect()->route('backend.course-enquiries.index')->with('success', 'Enquiry deleted.');
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
        }, 'course-enquiries-' . now()->format('Y-m-d-His') . '.csv', ['Content-Type' => 'text/csv']);
    }

    /** Download the (filtered) enquiries as a real .xlsx workbook. */
    public function exportExcel(Request $request): BinaryFileResponse
    {
        [$headings, $rows] = $this->exportData($request);

        return Excel::download(new ArrayExport($headings, $rows), 'course-enquiries-' . now()->format('Y-m-d-His') . '.xlsx');
    }

    /** Column headings + row arrays for the current filter — shared by CSV + Excel. */
    private function exportData(Request $request): array
    {
        $headings = ['ID', 'Course', 'Name', 'Email', 'Mobile', 'City', 'Career Goal', 'Message', 'Status', 'IP', 'Date'];

        $rows = $this->filtered($request)->with('course')->get()->map(fn ($r) => [
            $r->id, $r->course_name, $r->name, $r->email, $r->phone, $r->city,
            $r->career_goal, $r->message, $r->status, $r->ip_address,
            $r->created_at?->format('Y-m-d H:i'),
        ])->all();

        return [$headings, $rows];
    }

    /** Shared filter pipeline for the list + the export (search / status / date / course). */
    private function filtered(Request $request): Builder
    {
        return CourseEnquiry::query()
            ->latest()
            ->search($request->input('q'))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->input('status')))
            ->when($request->filled('course'), fn ($q) => $q->where('course_id', $request->integer('course')))
            ->when($request->filled('date'), fn ($q) => $q->whereDate('created_at', $request->input('date')));
    }
}
