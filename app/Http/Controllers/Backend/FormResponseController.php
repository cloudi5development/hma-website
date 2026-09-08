<?php

namespace App\Http\Controllers\Backend;

use App\Exports\ArrayExport;
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
        $formId = $request->integer('form');

        $responses = FormResponse::query()
            ->with(['form', ...FormResponse::WITH_ANSWERS])
            ->latest('submitted_at')->latest('id')
            ->search($request->input('q'))
            ->status($request->input('status'))
            ->when($formId, fn ($q) => $q->where('form_id', $formId))
            ->when($request->filled('date'), fn ($q) => $q->whereDate('submitted_at', $request->input('date')))
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
            fputcsv($out, $headings);
            foreach ($rows as $row) {
                fputcsv($out, $row);
            }
            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv']);
    }

    public function exportExcel(Request $request, Form $form): BinaryFileResponse
    {
        [$headings, $rows] = $this->exportData($request, $form);

        return Excel::download(
            new ArrayExport($headings, $rows),
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
        $fields   = $form->fields;
        $headings = ['ID', ...$fields->pluck('label')->all(), 'Status', 'Submitted'];

        $rows = [];

        $this->filtered($request, $form)->with(FormResponse::WITH_ANSWERS)->chunkById(500, function ($responses) use ($fields, &$rows) {
            foreach ($responses as $response) {
                $keyed = $response->keyed();

                $rows[] = [
                    $response->id,
                    ...$fields->map(fn ($field) => $keyed[$field->field_key]->display ?? '')->all(),
                    $response->status,
                    ($response->submitted_at ?? $response->created_at)->format('Y-m-d H:i'),
                ];
            }
        });

        return [$headings, $rows];
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
