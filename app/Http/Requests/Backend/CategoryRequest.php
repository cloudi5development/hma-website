<?php

namespace App\Http\Requests\Backend;

use App\Models\Category;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $creating = $this->isMethod('post');

        return [
            'department_id' => ['required', 'integer', 'exists:departments,id'],
            'name'          => ['required', 'string', 'max:120'],
            'description'   => ['nullable', 'string', 'max:600'],
            'icon'          => [$creating ? 'required' : 'nullable', 'image', 'mimes:webp,png,jpg,jpeg,svg', 'max:2048'],
            // Not on the form any more — the model hands the colour out on create.
            // Left in place so a seeder or a test may still set one deliberately.
            'tone'          => ['nullable', Rule::in(Category::TONES)],
            'is_active'     => ['nullable', 'boolean'],
            'show_home'     => ['nullable', 'boolean'],
            'is_featured'   => ['nullable', 'boolean'],
            'courses'       => ['nullable', 'array'],
            'courses.*'     => ['integer', 'exists:courses,id'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'is_active'   => $this->boolean('is_active'),
            'show_home'   => $this->boolean('show_home'),
            'is_featured' => $this->boolean('is_featured'),
        ]);
    }

    public function messages(): array
    {
        return [
            'icon.required' => 'Please upload a category icon.',
            'department_id.required' => 'Please choose a department.',
        ];
    }
}
