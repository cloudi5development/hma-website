<?php

namespace App\Http\Requests\Backend;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class BlogRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Route is already behind the admin.auth middleware.
        return true;
    }

    public function rules(): array
    {
        $creating = $this->isMethod('post');
        $blogId   = $this->route('blog')?->id;   // null on create

        return [
            'title'        => ['required', 'string', 'max:180'],
            'slug'         => ['required', 'string', 'max:200', Rule::unique('blogs', 'slug')->ignore($blogId)],
            'excerpt'      => ['required', 'string', 'max:500'],
            'content'      => ['required', 'string'],
            'image'        => [$creating ? 'required' : 'nullable', 'image', 'mimes:webp,png,jpg,jpeg', 'max:2048'],
            'author'       => ['nullable', 'string', 'max:120'],
            'category'     => ['nullable', 'string', 'max:80'],
            'published_at' => ['nullable', 'date'],
            'sort_order'   => ['nullable', 'integer', 'min:0', 'max:9999'],
            'is_active'    => ['nullable', 'boolean'],
            'show_home'    => ['nullable', 'boolean'],
            'is_latest'    => ['nullable', 'boolean'],
        ];
    }

    protected function prepareForValidation(): void
    {
        // Fall back to a slug derived from the title when the field is left blank.
        $slug = $this->input('slug');
        $slug = filled($slug) ? Str::slug($slug) : Str::slug((string) $this->input('title'));

        $this->merge([
            'slug'       => $slug,
            'author'     => $this->input('author') ?: 'Hireminds Academy Admin',
            'is_active'  => $this->boolean('is_active'),
            'show_home'  => $this->boolean('show_home'),
            'is_latest'  => $this->boolean('is_latest'),
            'sort_order' => $this->input('sort_order', 0),
        ]);
    }

    public function messages(): array
    {
        return [
            'image.required' => 'Please upload a cover image for the post.',
            'image.image'    => 'The cover must be an image (WebP, PNG or JPG).',
            'image.max'      => 'The cover may not be larger than 2 MB.',
            'slug.unique'    => 'That slug is already taken — choose another.',
            'content.required' => 'The article body cannot be empty.',
        ];
    }
}
