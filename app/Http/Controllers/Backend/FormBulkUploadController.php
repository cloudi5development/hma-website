<?php

namespace App\Http\Controllers\Backend;

use App\Exceptions\FormImportException;
use App\Exports\FormFieldsTemplateExport;
use App\Http\Controllers\Controller;
use App\Models\Form;
use App\Services\FormImportService;
use App\Support\UploadLimit;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Throwable;

/**
 * Forms → the builder's Bulk Upload.
 *
 * Two endpoints and no writes. The template is a download — a standard one, or
 * a quiz one with ?type=quiz. The preview reads the uploaded sheet, checks it
 * for the form type on screen, and answers with the checked rows and the
 * questions in order. The builder adds them to the section Bulk Upload was
 * opened from, and they are saved with the form, by the same code as every
 * other question — see FormImportService for why.
 *
 * Both routes sit inside admin.auth + admin.module under the forms.* names, so
 * only an admin who may edit forms reaches them.
 */
class FormBulkUploadController extends Controller
{
    public function __construct(private FormImportService $importer)
    {
    }

    /**
     * The template for the form type being built: ?type=quiz gives the quiz one
     * (Question and Correct Answer, no Placeholder), anything else the standard.
     */
    public function template(Request $request): BinaryFileResponse
    {
        $quiz = $request->query('type') === Form::QUIZ;

        return Excel::download(
            new FormFieldsTemplateExport($quiz),
            $quiz ? 'quiz-questions-template.xlsx' : 'form-fields-template.xlsx',
        );
    }

    public function preview(Request $request): JsonResponse
    {
        // The app sets no size limit on the sheet. The server does — PHP drops a
        // file larger than upload_max_filesize before Laravel sees it — so when
        // that is what happened, say so in those words rather than as a vague
        // "did not upload".
        $upload = $request->file('file');

        if ($upload && in_array($upload->getError(), [UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE], true)) {
            return response()->json([
                'message' => 'That file is larger than this server accepts (' . UploadLimit::label() . '). '
                    . 'Ask your hosting provider to raise upload_max_filesize, or split the sheet.',
            ], 422);
        }

        $validator = Validator::make($request->all(), [
            // The extension is checked here; whether the bytes really are a
            // spreadsheet is checked by FormImportService, which opens it with
            // the Xlsx and Xls readers and nothing else. MIME sniffing alone is
            // unreliable for these — an .xlsx is a zip, and is often reported
            // as one.
            'file'    => ['required', 'file', function ($attribute, $value, $fail) {
                if (! in_array(strtolower((string) $value->getClientOriginalExtension()), ['xlsx', 'xls'], true)) {
                    $fail('Upload an Excel file — .xlsx or .xls.');
                }
            }],
            'context' => ['nullable', 'string'],
        ], [
            'file.required' => 'Choose an Excel file to upload.',
            'file.file'     => 'The file did not upload. Try choosing it again.',
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => $validator->errors()->first()], 422);
        }

        try {
            $context = json_decode((string) $request->input('context', '{}'), true);
            $context = is_array($context) ? $context : [];

            $rows = $this->importer->read(
                $request->file('file')->getRealPath(),
                ($context['form_type'] ?? null) === Form::QUIZ,
            );

            return response()->json($this->importer->preview($rows, $context));
        } catch (FormImportException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        } catch (Throwable $e) {
            // Never the raw exception: it can carry paths and SQL, and it would
            // mean nothing to the person reading it.
            Log::warning('Form bulk upload could not be read', ['error' => $e->getMessage()]);

            return response()->json([
                'message' => 'Something went wrong reading that file. Check it is the downloaded template and try again.',
            ], 422);
        }
    }
}
