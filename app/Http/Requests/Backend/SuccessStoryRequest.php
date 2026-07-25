<?php

namespace App\Http\Requests\Backend;

use App\Models\SuccessStory;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SuccessStoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Route is already behind the admin.auth middleware.
        return true;
    }

    public function rules(): array
    {
        // On create the image is required; on update it is optional (keep existing).
        $creating = $this->isMethod('post');

        return [
            'name'       => ['required', 'string', 'max:120'],
            'role'       => ['nullable', 'string', 'max:120'],
            'salary'     => ['required', 'string', 'max:20'],
            'tone'       => ['required', Rule::in(SuccessStory::TONES)],
            'image'      => [$creating ? 'required' : 'nullable', 'image', 'mimes:webp,png,jpg,jpeg', 'max:2048'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:9999'],
            'is_active'  => ['nullable', 'boolean'],
            'show_home'  => ['nullable', 'boolean'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'is_active'  => $this->boolean('is_active'),
            'show_home'  => $this->boolean('show_home'),
            'sort_order' => $this->input('sort_order', 0),
        ]);
    }

    public function messages(): array
    {
        return [
            'image.required' => 'Please upload the student\'s photo.',
            'image.image'    => 'The photo must be an image (WebP, PNG or JPG).',
            'image.max'      => 'The photo may not be larger than 2 MB.',
            'salary.required'=> 'Please enter the package (LPA).',
        ];
    }
}
