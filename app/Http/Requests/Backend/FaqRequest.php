<?php

namespace App\Http\Requests\Backend;

use Illuminate\Foundation\Http\FormRequest;

class FaqRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Route is already behind the admin.auth middleware.
        return true;
    }

    public function rules(): array
    {
        return [
            'question'          => ['required', 'string', 'max:255'],
            'answer'            => ['required', 'string', 'max:2000'],
            'sort_order'        => ['nullable', 'integer', 'min:0', 'max:9999'],
            'is_active'         => ['nullable', 'boolean'],
            'show_home'         => ['nullable', 'boolean'],
            'show_about'        => ['nullable', 'boolean'],
            'show_courses'      => ['nullable', 'boolean'],
            'show_testimonials' => ['nullable', 'boolean'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'is_active'         => $this->boolean('is_active'),
            'show_home'         => $this->boolean('show_home'),
            'show_about'        => $this->boolean('show_about'),
            'show_courses'      => $this->boolean('show_courses'),
            'show_testimonials' => $this->boolean('show_testimonials'),
            'sort_order'        => $this->input('sort_order', 0),
        ]);
    }
}
