<?php

namespace App\Http\Requests\Backend;

use Illuminate\Foundation\Http\FormRequest;

class TestimonialRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Route is already behind the admin.auth middleware.
        return true;
    }

    public function rules(): array
    {
        // On create the photo is required; on update it is optional (keep existing).
        $creating = $this->isMethod('post');

        return [
            'name'              => ['required', 'string', 'max:120'],
            'role'              => ['nullable', 'string', 'max:120'],
            'company'           => ['nullable', 'string', 'max:120'],
            'review'            => ['required', 'string', 'max:1000'],
            'rating'            => ['required', 'integer', 'min:1', 'max:5'],
            'photo'             => [$creating ? 'required' : 'nullable', 'image', 'mimes:webp,png,jpg,jpeg', 'max:2048'],
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
            'rating'            => $this->input('rating', 5),
        ]);
    }

    public function messages(): array
    {
        return [
            'photo.required' => 'Please upload a photo of the reviewer.',
            'photo.image'    => 'The photo must be an image (WebP, PNG or JPG).',
            'photo.max'      => 'The photo may not be larger than 2 MB.',
        ];
    }
}
