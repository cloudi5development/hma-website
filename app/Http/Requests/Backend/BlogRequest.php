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
            'image_alt'    => ['nullable', 'string', 'max:180'],
            'sort_order'   => ['nullable', 'integer', 'min:0', 'max:9999'],
            'is_active'    => ['nullable', 'boolean'],
            'show_home'    => ['nullable', 'boolean'],
            'is_latest'    => ['nullable', 'boolean'],

            // Per-post SEO. Same limits as a course's, so both editors behave
            // alike; blank falls back to the title / excerpt on the page.
            'meta_title'       => ['nullable', 'string', 'max:180'],
            'meta_description' => ['nullable', 'string', 'max:300'],
            'meta_keywords'    => ['nullable', 'string', 'max:255'],
        ];
    }

    protected function prepareForValidation(): void
    {
        // Fall back to a slug derived from the title when the field is left blank.
        $slug = $this->input('slug');
        $slug = filled($slug) ? Str::slug($slug) : Str::slug((string) $this->input('title'));

        $this->merge([
            'content'    => $this->emptyEditorToNull($this->input('content')),
            'slug'       => $slug,
            'author'     => $this->input('author') ?: 'Hireminds Academy Admin',
            'is_active'  => $this->boolean('is_active'),
            'show_home'  => $this->boolean('show_home'),
            'is_latest'  => $this->boolean('is_latest'),
            'sort_order' => $this->input('sort_order', 0),
        ]);
    }

    /**
     * The editor never hands back a truly empty field: clearing it leaves
     * "<p></p>" or "<p>&nbsp;<br></p>" behind. Those look empty on the page, so
     * they must fail "required" like an empty box does rather than saving an
     * article with nothing in it.
     */
    private function emptyEditorToNull(mixed $content): mixed
    {
        if (! is_string($content)) {
            return $content;
        }

        // An article can carry something that is not text - a table, a rule,
        // an embedded image pasted through the code view - and stripping tags
        // would leave those looking blank.
        if (preg_match('/<(img|iframe|video|table|hr)\b/i', $content)) {
            return $content;
        }

        // Tags first, then entities: decoding first could turn "&lt;p&gt;"
        // into a tag that strip_tags would then eat. The NBSP is trimmed by
        // name - it is what the editor leaves behind, and trim() does not
        // count it as whitespace.
        $text = html_entity_decode(strip_tags($content), ENT_QUOTES | ENT_HTML5, 'UTF-8');

        return trim($text, " \t\n\r\0\x0B\u{00A0}") === '' ? null : $content;
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
