<?php

namespace App\Http\Controllers\Backend;

use App\Exports\FormResponsesExport;
use App\Http\Controllers\Backend\Concerns\HandlesTableQuery;
use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Form;
use App\Models\FormResponse;
use App\Models\FormResponseValue;
use App\Services\FormSubmissionService;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Forms → a form → Responses.
 *
 * Every table here is built at request time from the form's own fields, because
 * there is no other way: two forms in this module share no columns. The listing
 * picks the first few fields as columns and the detail page shows all of them.
 */
class FormResponseController extends Controller
{
    use HandlesTableQuery;

    /** Field columns the listing shows before it stops and defers to the detail page. */
    private const LIST_COLUMNS = 5;

    /**
     * Forms → Responses: everything that has come in, across every form.
     *
     * The per-form listing below can build its columns from that form's own
     * questions. This one cannot — two forms here share no fields — so it shows
     * what IS common to every response (which form, when, what state) plus a
     * summary of the first couple of answers, and links through for the rest.
     */
    public function all(Request $request): View
    {
        $this->scalarFilters($request);

        $formId = $request->integer('form');

        $responses = $this->filteredAcrossForms($request)
            ->with(['form', ...FormResponse::WITH_ANSWERS])
            ->paginate($this->perPage())->withQueryString();

        return view('backend.forms.responses.all', [
            'responses' => $responses,
            'statuses'  => FormResponse::STATUSES,
            // Only forms that have actually collected something, so the filter
            // is not a list of every form ever made.
            'forms'     => Form::query()->has('responses')->orderBy('name')->get(['id', 'name']),
            'formId'    => $formId,
        ]);
    }

    public function index(Request $request, Form $form): View
    {
        $this->scalarFilters($request);

        $responses = $this->filtered($request, $form)
            // The field's choices ride along because an answer is displayed by
            // its label — without them that is a query per answer.
            ->with(FormResponse::WITH_ANSWERS)
            ->paginate($this->perPage())->withQueryString();

        return view('backend.forms.responses.index', [
            'form'      => $form,
            'responses' => $responses,
            'columns'   => $this->columns($form),
            'statuses'  => FormResponse::STATUSES,
        ]);
    }

    public function show(Form $form, FormResponse $response): View
    {
        abort_unless($response->form_id === $form->id, 404);

        ActivityLog::record('Response Viewed', "Viewed response #{$response->id} of form “{$form->name}”");

        return view('backend.forms.responses.show', [
            'form'     => $form,
            'response' => $response->load(FormResponse::WITH_ANSWERS),
            'statuses' => FormResponse::STATUSES,
        ]);
    }

    public function updateStatus(Request $request, Form $form, FormResponse $response): RedirectResponse
    {
        abort_unless($response->form_id === $form->id, 404);

        Validator::make($request->all(), [
            'status' => ['required', 'in:' . implode(',', FormResponse::STATUSES)],
        ])->validate();

        $response->update(['status' => $request->input('status')]);

        ActivityLog::record('Status Updated', "Response #{$response->id} of “{$form->name}” → {$request->input('status')}");

        return back()->with('success', 'Status updated to ' . $request->input('status') . '.');
    }

    public function destroy(Form $form, FormResponse $response): RedirectResponse
    {
        abort_unless($response->form_id === $form->id, 404);

        FormSubmissionService::deleteUploads($response->load('values'));

        ActivityLog::record('Response Deleted', "Response #{$response->id} of form “{$form->name}” deleted");

        $response->delete();

        return redirect()->route('backend.forms.responses.index', $form)->with('success', 'Response deleted.');
    }

    /**
     * Delete one response without naming its form.
     *
     * A response can outlive its form. It should not — form_responses.form_id
     * cascades on delete — but it does wherever that constraint is not actually
     * enforced: a MyISAM table silently ignores foreign keys, and deleting a
     * form row by hand with FOREIGN_KEY_CHECKS off skips the cascade outright.
     *
     * The Responses screen used to hide its delete button on such a row, since
     * the form-scoped route above has no form to point at. That left the one
     * row an admin most wants gone as the one row they cannot remove.
     */
    public function destroyAny(FormResponse $response): RedirectResponse
    {
        FormSubmissionService::deleteUploads($response->load('values'));

        ActivityLog::record('Response Deleted', "Response #{$response->id} deleted");

        $response->delete();

        return back()->with('success', 'Response deleted.');
    }

    /* ================================ CLEAR ================================
       Emptying a form's responses — the thing you need after testing a form and
       before it goes live, when deleting fifty rows one at a time is not a
       plan.

       Both entry points delete exactly what the current filter is showing, so
       the button can never remove more than the admin is looking at. With no
       filter applied that is everything, which is the usual case. */

    /** Clear one form's responses. */
    public function clear(Request $request, Form $form): RedirectResponse
    {
        $this->scalarFilters($request);

        $deleted = $this->deleteMatching($this->filtered($request, $form));

        ActivityLog::record('Responses Cleared', "{$deleted} response(s) of form “{$form->name}” deleted");

        return back()->with('success', $deleted === 0
            ? 'There was nothing to delete.'
            : $deleted . ' response' . ($deleted === 1 ? '' : 's') . ' deleted.');
    }

    /** Clear responses across every form, or across the one being filtered to. */
    public function clearAll(Request $request): RedirectResponse
    {
        $this->scalarFilters($request);

        $deleted = $this->deleteMatching($this->filteredAcrossForms($request));

        ActivityLog::record('Responses Cleared', "{$deleted} response(s) deleted from the Responses screen");

        return back()->with('success', $deleted === 0
            ? 'There was nothing to delete.'
            : $deleted . ' response' . ($deleted === 1 ? '' : 's') . ' deleted.');
    }

    /**
     * Delete every response a query matches, and the files they carried.
     *
     * The uploads live outside the database, so the rows cascading is not
     * enough — a cleared form would otherwise leave its files on disk forever.
     * Chunked, because "clear this form" is exactly the moment there are tens of
     * thousands of rows and loading them all is how the page runs out of memory.
     */
    private function deleteMatching(Builder $query): int
    {
        $deleted = 0;

        // Ordered by id and re-queried each pass: chunkById pages forward on the
        // key, so rows disappearing underneath it does not skip any.
        $query->reorder()->with('values')->chunkById(200, function ($responses) use (&$deleted) {
            foreach ($responses as $response) {
                FormSubmissionService::deleteUploads($response);
            }

            $deleted += FormResponse::whereIn('id', $responses->modelKeys())->delete();
        });

        return $deleted;
    }

    /* =============================== DOWNLOAD ============================== */

    /**
     * Stream one uploaded file.
     *
     * Form uploads are deliberately NOT on the public disk — a resume is not a
     * course thumbnail — so this route is the only way to them, and it sits
     * behind the same admin.auth + admin.module guard as the rest of the panel.
     *
     * The stored path is checked against the value that claims it AND against
     * the module's own directory, so a tampered id cannot be turned into a read
     * of some other file on the disk.
     */
    public function download(Form $form, FormResponse $response, FormResponseValue $value, string $index = '0')
    {
        abort_unless($response->form_id === $form->id, 404);
        abort_unless($value->response_id === $response->id, 404);

        $path = $value->filePaths()[(int) $index] ?? null;

        abort_if($path === null, 404);
        abort_unless(str_starts_with($path, FormSubmissionService::UPLOAD_ROOT . '/' . $form->id . '/'), 404);

        $disk = Storage::disk('local');

        abort_unless($disk->exists($path), 404);

        return $disk->download($path, FormResponseValue::originalName($path));
    }

    /* ================================ EXPORT =============================== */

    public function export(Request $request, Form $form): StreamedResponse
    {
        [$headings, $rows] = $this->exportData($request, $form);

        $filename = $form->slug . '-responses-' . now()->format('Y-m-d-His') . '.csv';

        return response()->streamDownload(function () use ($headings, $rows) {
            $out = fopen('php://output', 'w');

            // A byte-order mark, so Excel opens the file as UTF-8. Without it
            // Excel assumes the local code page, and every name with an accent,
            // every ₹ and every curly quote a visitor typed arrives garbled.
            fwrite($out, "\xEF\xBB\xBF");

            fputcsv($out, array_map(fn ($cell) => self::csvCell($cell), $headings));
            foreach ($rows as $row) {
                fputcsv($out, array_map(fn ($cell) => self::csvCell($cell), $row));
            }
            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    /**
     * One CSV cell, made safe to open in a spreadsheet.
     *
     * Every answer here was typed by the public. A cell beginning with = + - or
     * @ is run as a FORMULA when the file is opened in Excel — so an answer of
     * =HYPERLINK("https://…?"&B2,"open") would build a link out of other
     * people's answers. The usual defence: a leading apostrophe, which a
     * spreadsheet reads as "this is text". A phone number or plain number
     * ("+91 98765 43210", "-5") is left alone: there is nothing to run in it.
     */
    private static function csvCell(mixed $value): mixed
    {
        if (! is_string($value) || $value === '') {
            return $value;
        }

        if (in_array($value[0], ['=', '+', '-', '@', "\t", "\r"], true)
            && ! preg_match('/^[+-]?[\d\s().\-]+$/', $value)) {
            return "'" . $value;
        }

        return $value;
    }

    public function exportExcel(Request $request, Form $form): BinaryFileResponse
    {
        [$headings, $rows] = $this->exportData($request, $form);

        return Excel::download(
            new FormResponsesExport($headings, $rows),
            $form->slug . '-responses-' . now()->format('Y-m-d-His') . '.xlsx',
        );
    }

    /**
     * Headings and rows for the current filter, shared by both exports.
     *
     * The columns are this form's live fields — so an export of a job
     * application carries Applicant / Resume / Experience, and an export of a
     * course enquiry carries Student Name / Mobile / Course, with no code
     * knowing either.
     *
     * Chunked rather than ->get(): a form that has collected tens of thousands
     * of responses must not be loaded into memory in one go.
     */
    private function exportData(Request $request, Form $form): array
    {
        $this->scalarFilters($request);

        // The live questions, then any that were removed while they held
        // answers. The builder keeps those (soft-deleted) precisely so their
        // answers stay readable — and the response page shows them — so an
        // export that left them out silently dropped part of what was collected.
        $fields = $form->fields->concat(
            $form->allFields()->onlyTrashed()->get()
        );

        $headings = ['ID', ...$fields->map(fn ($f) => $f->trashed() ? $f->label . ' (removed)' : $f->label)->all(), 'Status', 'Submitted'];

        $rows = [];

        // Newest first, paged on the id itself. The filter orders by
        // submitted_at, and chunkById pages with "id > last id seen" while
        // keeping that order — so every chunk after the first re-read the
        // newest rows: 1,200 responses exported as 999 rows, 499 of them twice
        // and the oldest 700 missing. reorder() + chunkByIdDesc walks the
        // table once, in one order, with nothing skipped.
        $this->filtered($request, $form)->reorder()->with(FormResponse::WITH_ANSWERS)
            ->chunkByIdDesc(500, function ($responses) use ($fields, &$rows) {
                foreach ($responses as $response) {
                    $rows[] = [
                        $response->id,
                        ...$fields->map(fn ($field) => $response->answerFor($field)?->display ?? '')->all(),
                        $response->status,
                        ($response->submitted_at ?? $response->created_at)->format('Y-m-d H:i'),
                    ];
                }
            });

        return [$headings, $rows];
    }

    /**
     * The filter parameters, as the plain strings every query and view here
     * expects. A hand-edited URL such as ?q[]=x or ?status[]=x arrived as an
     * array and turned the whole screen into a server error; it is read as
     * "no filter" instead.
     */
    private function scalarFilters(Request $request): void
    {
        foreach (['q', 'status', 'date', 'form', 'per_page'] as $key) {
            if (is_array($request->input($key))) {
                $request->merge([$key => null]);
                $request->query->remove($key);
            }
        }
    }

    /* ================================ SHARED =============================== */

    /** The field columns the listing shows. Hidden fields and files are poor table cells. */
    private function columns(Form $form)
    {
        return $form->fields
            ->reject(fn ($field) => $field->isHidden())
            ->take(self::LIST_COLUMNS)
            ->values();
    }

    /**
     * The cross-form filter, shared by the Responses listing and the clear
     * action, so the button can only ever delete what the screen is showing.
     */
    private function filteredAcrossForms(Request $request): Builder
    {
        return FormResponse::query()
            ->latest('submitted_at')->latest('id')
            ->search($request->input('q'))
            ->status($request->input('status'))
            ->when($request->integer('form'), fn ($q, $id) => $q->where('form_id', $id))
            ->when($request->filled('date'), fn ($q) => $q->whereDate('submitted_at', $request->input('date')));
    }

    /** Search / status / date, applied identically to the list and the exports. */
    private function filtered(Request $request, Form $form): Builder
    {
        return FormResponse::query()
            ->where('form_id', $form->id)
            ->latest('submitted_at')->latest('id')
            ->search($request->input('q'))
            ->status($request->input('status'))
            ->when($request->filled('date'), fn ($q) => $q->whereDate('submitted_at', $request->input('date')));
    }
}
