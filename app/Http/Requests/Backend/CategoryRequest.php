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
        $id = $this->route('category')?->id;

        return [
            'department_id' => ['required', 'integer', 'exists:departments,id'],
            'name'          => ['required', 'string', 'max:120'],
            'slug'          => ['nullable', 'string', 'max:140', 'alpha_dash', Rule::unique('categories', 'slug')->ignore($id)],
            'description'   => ['nullable', 'string', 'max:600'],
            'icon'          => [$creating ? 'required' : 'nullable', 'image', 'mimes:webp,png,jpg,jpeg,svg', 'max:2048'],
            'tone'          => ['nullable', Rule::in(Category::TONES)],
            'sort_order'    => ['nullable', 'integer', 'min:0', 'max:9999'],
            'is_active'     => ['nullable', 'boolean'],
            'show_home'     => ['nullable', 'boolean'],
            'is_featured'   => ['nullable', 'boolean'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'is_active'   => $this->boolean('is_active'),
            'show_home'   => $this->boolean('show_home'),
            'is_featured' => $this->boolean('is_featured'),
            'sort_order'  => $this->input('sort_order', 0),
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
