<?php

namespace App\Http\Requests\Backend;

use Illuminate\Foundation\Http\FormRequest;

class CounterRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Route is already behind the admin.auth middleware.
        return true;
    }

    public function rules(): array
    {
        return [
            'label'             => ['required', 'string', 'max:120'],
            // Display string, e.g. "2.5K+", "150+", "95%". Must start with a
            // number; the model derives the count-up target/suffix/decimals.
            'number'            => ['required', 'string', 'max:20', 'regex:/^\d/'],
            'sort_order'        => ['nullable', 'integer', 'min:0', 'max:9999'],
            'is_active'         => ['nullable', 'boolean'],
            'show_home'         => ['nullable', 'boolean'],
            'show_about'        => ['nullable', 'boolean'],
            'show_testimonials' => ['nullable', 'boolean'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'is_active'         => $this->boolean('is_active'),
            'show_home'         => $this->boolean('show_home'),
            'show_about'        => $this->boolean('show_about'),
            'show_testimonials' => $this->boolean('show_testimonials'),
            'sort_order'        => $this->input('sort_order', 0),
        ]);
    }

    public function messages(): array
    {
        return [
            'number.regex' => 'The value must start with a number, e.g. 2.5K+, 150+ or 95%.',
        ];
    }
}
