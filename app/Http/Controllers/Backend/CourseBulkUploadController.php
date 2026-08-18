<?php

namespace App\Http\Controllers\Backend;

use App\Exports\ArrayExport;
use App\Exports\CourseTemplateExport;
use App\Http\Controllers\Controller;
use App\Http\Requests\Backend\CourseBulkUploadRequest;
use App\Imports\CourseRowsImport;
use App\Models\ActivityLog;
use App\Services\CourseBulkImportService;
use App\Support\CourseImportTemplate;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * Bulk course upload: download a template, fill it in, upload it, look at what
 * the importer made of it, then confirm.
 *
 * Nothing is written until the admin confirms. The upload step parks the file on
 * the private disk and writes a report beside it; the preview reads that report;
 * the confirm step reads the FILE again and re-validates from scratch, so the
 * decision to insert is never taken from data that has been sitting in a browser.
 *
 * This controller creates courses only. It never touches images or brochures —
 * those stay with the manual Course Edit screen — and it never updates an
 * existing course.
 */
class CourseBulkUploadController extends Controller
{
    /** Where parked uploads live, on the private (non-web-served) disk. */
    private const DIR = 'course-imports';

    /** Rows shown in the preview table. The report itself keeps every row. */
    private const PREVIEW_ROWS = 200;

    public function __construct(private CourseBulkImportService $importer)
    {
    }

    /** Step 1–3: the upload page. */
    public function form(): View
    {
        return view('backend.courses.bulk-upload');
    }

    /** The blank .xlsx template — headers, dropdowns, and the reference sheets. */
    public function template(): BinaryFileResponse
    {
        return Excel::download(
            CourseTemplateExport::template(),
            'hireminds-course-template-' . now()->format('Y-m-d') . '.xlsx',
        );
    }

    /**
     * Every existing course, in the same workbook as the template.
     *
     * This is the front half of the round trip: export, edit the rows, upload the
     * same file back. Rows keep their `slug`, which is what makes the re-upload
     * an update of those courses rather than a second copy of them.
     */
    public function export(): BinaryFileResponse
    {
        return Excel::download(
            CourseTemplateExport::withData(),
            'hireminds-courses-' . now()->format('Y-m-d-His') . '.xlsx',
        );
    }

    /**
     * Read the uploaded file and report on it. Writes no courses.
     */
    public function check(CourseBulkUploadRequest $request): RedirectResponse
    {
        $file = $request->file('file');

        // Never trust the uploaded name. It is kept only to show the admin what
        // they uploaded; the file on disk gets a random name and its extension
        // comes from the validated type, not from the string the browser sent.
        $token     = (string) Str::uuid();
        $extension = strtolower($file->getClientOriginalExtension() ?: 'xlsx');
        $extension = in_array($extension, ['xlsx', 'csv', 'txt'], true) ? $extension : 'xlsx';
        $path      = self::DIR . '/' . $token . '.' . $extension;

        Storage::disk('local')->putFileAs(self::DIR, $file, $token . '.' . $extension);

        $reader = new CourseRowsImport();

        try {
            Excel::import($reader, $path, 'local');
        } catch (\Throwable $e) {
            // The reader's own exceptions name internal paths and library
            // classes; the admin gets the readable half and the detail goes to
            // the log.
            Log::error('Course bulk upload could not be read', [
                'file'      => $file->getClientOriginalName(),
                'exception' => $e,
            ]);

            Storage::disk('local')->delete($path);

            return back()->with('error', 'That file could not be read. Make sure it is a valid .xlsx or .csv saved from the template.');
        }

        // ---- File-level checks --------------------------------------------
        // A file with a good heading row and no data under it teaches the reader
        // nothing, so the headings are read straight off row 1 in that case.
        $reader->applyFallbackHeadings(CourseRowsImport::headingsOf($path, 'local'));

        $missing = $reader->missingHeadings();

        if ($missing) {
            Storage::disk('local')->delete($path);

            return back()->with('error', 'The file is missing required column'
                . (count($missing) === 1 ? '' : 's') . ': ' . implode(', ', $missing)
                . '. Download the template and use its heading row.');
        }

        $rows = $reader->rows();

        if ($rows->isEmpty()) {
            Storage::disk('local')->delete($path);

            return back()->with('error', 'That file has a heading row but no course rows in it.');
        }

        $report = $this->importer->validateRows($rows);

        $report['file']      = $file->getClientOriginalName();
        $report['path']      = $path;
        $report['truncated'] = $reader->truncatedCount();

        Storage::disk('local')->put($this->reportPath($token), json_encode($report));

        // The token is the only handle, and it is held in the session — a
        // parked upload cannot be reached by guessing a URL.
        $request->session()->put('course_import_token', $token);

        return redirect()->route('backend.courses.bulk.preview');
    }

    /** The preview: totals, a row-by-row table, and the confirm button. */
    public function preview(Request $request): RedirectResponse|View
    {
        $report = $this->report($request);

        if (! $report) {
            return redirect()->route('backend.courses.bulk.form')
                ->with('error', 'That upload has expired. Please upload the file again.');
        }

        return view('backend.courses.bulk-preview', [
            'report'      => $report,
            'summary'     => $report['summary'],
            'previewRows' => array_slice($report['rows'], 0, self::PREVIEW_ROWS),
            'hiddenRows'  => max(0, count($report['rows']) - self::PREVIEW_ROWS),
        ]);
    }

    /**
     * Confirmed: create the courses that pass.
     *
     * The file is re-read and every row re-validated rather than trusting the
     * parked report — between the preview and this click, another admin may have
     * created a course that makes one of these rows a duplicate.
     */
    public function import(Request $request): RedirectResponse
    {
        $report = $this->report($request);

        if (! $report) {
            return redirect()->route('backend.courses.bulk.form')
                ->with('error', 'That upload has expired. Please upload the file again.');
        }

        $reader = new CourseRowsImport();

        try {
            Excel::import($reader, $report['path'], 'local');
            $result = $this->importer->import($reader->rows());
        } catch (\Throwable $e) {
            Log::error('Course bulk import failed', [
                'file'      => $report['file'],
                'exception' => $e,
            ]);

            return back()->with('error', 'The import could not be completed and nothing was saved. The error has been logged — please try again.');
        }

        $result['file']      = $report['file'];
        $result['path']      = $report['path'];
        $result['truncated'] = $report['truncated'] ?? 0;

        // Kept so the error report stays downloadable from the summary screen.
        Storage::disk('local')->put($this->reportPath($this->token($request)), json_encode($result));

        ActivityLog::record('Bulk course import', sprintf(
            '%s — %d row%s read, %d created, %d updated, %d duplicate, %d error%s.',
            $report['file'],
            $result['summary']['total'],
            $result['summary']['total'] === 1 ? '' : 's',
            $result['summary']['create'],
            $result['summary']['update'],
            $result['summary']['duplicates'],
            $result['summary']['errors'],
            $result['summary']['errors'] === 1 ? '' : 's',
        ));

        $request->session()->put('course_import_done', true);

        return redirect()->route('backend.courses.bulk.summary');
    }

    /** What happened, after the import ran. */
    public function summary(Request $request): RedirectResponse|View
    {
        $report = $this->report($request);

        if (! $report || ! $request->session()->get('course_import_done')) {
            return redirect()->route('backend.courses.bulk.form');
        }

        return view('backend.courses.bulk-summary', [
            'report'  => $report,
            'summary' => $report['summary'],
        ]);
    }

    /**
     * The error report: one line per row that did not import.
     *
     * Uses the panel's existing ArrayExport rather than a new export class —
     * headings plus rows is all this needs.
     */
    public function errors(Request $request): RedirectResponse|BinaryFileResponse
    {
        $report = $this->report($request);

        if (! $report) {
            return redirect()->route('backend.courses.bulk.form')
                ->with('error', 'That upload has expired. Please upload the file again.');
        }

        $headings = ['row_number', 'course_name', 'category', 'slug', 'error_type', 'error_message'];

        // Everything that will NOT be written — errors and duplicates alike.
        $written = [CourseBulkImportService::CREATE, CourseBulkImportService::UPDATE];

        $rows = collect($report['rows'])
            ->reject(fn ($row) => in_array($row['status'], $written, true))
            ->map(fn ($row) => [
                $row['line'],
                $row['course_name'] ?? '',
                $row['category'] ?? '',
                $row['slug'] ?? '',
                $row['error_type'] ?? '',
                implode(' ', $row['errors']),
            ])
            ->values()
            ->all();

        return Excel::download(
            new ArrayExport($headings, $rows),
            'course-import-errors-' . now()->format('Y-m-d-His') . '.xlsx',
        );
    }

    /** Throw the parked file away and start again. */
    public function cancel(Request $request): RedirectResponse
    {
        $this->discard($request);

        return redirect()->route('backend.courses.bulk.form')
            ->with('success', 'Upload cancelled. Nothing was imported.');
    }

    // ---------------------------------------------------------------- helpers

    private function token(Request $request): ?string
    {
        $token = $request->session()->get('course_import_token');

        // Session values are attacker-influenced in principle; this one is used
        // to build a path, so it is only accepted in the shape it was issued in.
        return is_string($token) && Str::isUuid($token) ? $token : null;
    }

    /** The parked report, or null when there isn't one any more. */
    private function report(Request $request): ?array
    {
        $token = $this->token($request);

        if (! $token || ! Storage::disk('local')->exists($this->reportPath($token))) {
            return null;
        }

        $report = json_decode(Storage::disk('local')->get($this->reportPath($token)), true);

        return is_array($report) ? $report : null;
    }

    private function reportPath(string $token): string
    {
        return self::DIR . '/' . $token . '.report.json';
    }

    /** Remove the parked upload and its report. */
    private function discard(Request $request): void
    {
        $token = $this->token($request);

        if ($token) {
            $disk = Storage::disk('local');

            foreach ($disk->files(self::DIR) as $file) {
                if (str_contains($file, $token)) {
                    $disk->delete($file);
                }
            }
        }

        $request->session()->forget(['course_import_token', 'course_import_done']);
    }
}
