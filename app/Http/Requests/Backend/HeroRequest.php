<?php

namespace App\Http\Requests\Backend;

use Illuminate\Foundation\Http\FormRequest;

class HeroRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Route is already behind the admin.auth middleware.
        return true;
    }

    public function rules(): array
    {
        // The hero is a seeded singleton that always carries an image, so a new
        // upload is optional (the controller keeps the existing one otherwise).
        return [
            'badge_text'  => ['required', 'string', 'max:160'],
            'title'       => ['required', 'string', 'max:200'],
            'description' => ['required', 'string', 'max:600'],
            'btn1_text'   => ['required', 'string', 'max:60'],
            'btn1_url'    => ['nullable', 'string', 'max:255'],
            'btn2_text'   => ['required', 'string', 'max:60'],
            'btn2_url'    => ['nullable', 'string', 'max:255'],
            'image'       => ['nullable', 'image', 'mimes:webp,png,jpg,jpeg', 'max:3072'],
        ];
    }

    public function messages(): array
    {
        return [
            'image.required' => 'Please upload a hero image.',
            'image.image'    => 'The hero image must be an image (WebP, PNG or JPG).',
            'image.max'      => 'The hero image may not be larger than 3 MB.',
        ];
    }
}
