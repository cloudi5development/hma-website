<?php

namespace App\Http\Requests\Backend;

use Illuminate\Foundation\Http\FormRequest;

class PartnerRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Route is already behind the admin.auth middleware.
        return true;
    }

    public function rules(): array
    {
        // On create the logo is required; on update it is optional (keep existing).
        $creating = $this->isMethod('post');

        return [
            'name'              => ['required', 'string', 'max:120'],
            'logo'              => [$creating ? 'required' : 'nullable', 'image', 'mimes:webp,png,jpg,jpeg,svg', 'max:2048'],
            'sort_order'        => ['nullable', 'integer', 'min:0', 'max:9999'],
            'is_active'         => ['nullable', 'boolean'],
            'show_home'         => ['nullable', 'boolean'],
            'show_about'        => ['nullable', 'boolean'],
            'show_testimonials' => ['nullable', 'boolean'],
        ];
    }

    protected function prepareForValidation(): void
    {
        // Unchecked checkboxes don't submit — normalise them to false.
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
            'logo.required' => 'Please upload a partner logo.',
            'logo.image'    => 'The logo must be an image (WebP, PNG, JPG or SVG).',
            'logo.max'      => 'The logo may not be larger than 2 MB.',
        ];
    }
}
