<?php

namespace App\Http\Requests\Backend;

use App\Support\UploadLimit;
use Illuminate\Foundation\Http\FormRequest;

class CourseBulkUploadRequest extends FormRequest
{
    /** What the feature would like to allow, before the server has its say. */
    public const PREFERRED_MAX_KB = 10 * 1024;

    public function authorize(): bool
    {
        // Route sits behind admin.auth + admin.module ("courses").
        return true;
    }

    public function rules(): array
    {
        return [
            // `mimes` checks the extension AND the guessed type, so a .php
            // renamed to .xlsx is refused before it reaches the reader. txt is
            // allowed because a plain CSV is very often detected as text/plain.
            'file' => [
                'required',
                'file',
                'mimes:xlsx,csv,txt',
                'max:' . UploadLimit::cap(self::PREFERRED_MAX_KB),
            ],
        ];
    }

    public function messages(): array
    {
        $max = UploadLimit::label(UploadLimit::cap(self::PREFERRED_MAX_KB));

        return [
            'file.required' => 'Choose the filled-in Excel or CSV file to upload.',
            'file.mimes'    => 'The file must be an .xlsx or .csv — download the template if you are unsure.',
            'file.max'      => "The file may not be larger than {$max}.",
            'file.uploaded' => "The file could not be uploaded. Check that it is under {$max} and try again.",
        ];
    }
}
