<?php

namespace App\Http\Requests;

use App\Support\ApiResponse;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

/**
 * Base class for all FormRequests. For API / JSON requests it returns the
 * standard ApiResponse validation-error envelope (422) instead of the default
 * redirect-back-with-errors behaviour used by web forms.
 *
 * Extend this instead of Illuminate\Foundation\Http\FormRequest.
 */
abstract class BaseFormRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function failedValidation(Validator $validator): void
    {
        if ($this->expectsJson() || $this->is('api/*')) {
            throw new HttpResponseException(
                ApiResponse::error('Validation failed.', $validator->errors(), 422)
            );
        }

        parent::failedValidation($validator);
    }
}
