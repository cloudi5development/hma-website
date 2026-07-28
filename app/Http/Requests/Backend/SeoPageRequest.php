<?php

namespace App\Http\Requests\Backend;

use App\Models\SeoPage;
use Closure;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SeoPageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $id = $this->route('seo_page')?->id;

        return [
            'key'   => ['required', 'string', 'max:120', 'regex:/^[a-z0-9\-\.]+$/', Rule::unique('seo_pages', 'key')->ignore($id)],
            'label' => ['required', 'string', 'max:120'],

            // Meta
            'title'            => ['nullable', 'string', 'max:180'],
            'meta_description' => ['nullable', 'string', 'max:320'],
            'meta_keywords'    => ['nullable', 'string', 'max:320'],
            'meta_robots'      => ['nullable', Rule::in(array_keys(SeoPage::ROBOTS))],
            'canonical_url'    => ['nullable', 'string', 'max:255', 'url'],

            // Social
            'og_title'              => ['nullable', 'string', 'max:180'],
            'og_description'        => ['nullable', 'string', 'max:320'],
            'og_image'              => ['nullable', 'image', 'mimes:webp,png,jpg,jpeg', 'max:2048'],
            'twitter_title'         => ['nullable', 'string', 'max:180'],
            'twitter_description'   => ['nullable', 'string', 'max:320'],
            'twitter_image'         => ['nullable', 'image', 'mimes:webp,png,jpg,jpeg', 'max:2048'],

            // Schema — free-form JSON-LD, parsed on save so a typo can't reach the page
            'schema_json'       => ['nullable', 'string', $this->validJson('object')],
            'breadcrumb_schema' => ['nullable', 'string', $this->validJson('list', ['name'])],
            'faq_schema'        => ['nullable', 'string', $this->validJson('list', ['question', 'answer'])],

            'is_active' => ['nullable', 'boolean'],
        ];
    }

    /**
     * JSON-LD checker: parses the value and confirms its shape, so the page head
     * never receives something a crawler will choke on.
     *
     * @param  'object'|'list'  $shape
     * @param  array<int,string>  $requiredKeys  keys every list entry must carry
     */
    private function validJson(string $shape, array $requiredKeys = []): Closure
    {
        return function (string $attribute, mixed $value, Closure $fail) use ($shape, $requiredKeys) {
            $decoded = json_decode((string) $value, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                $fail('That is not valid JSON — ' . json_last_error_msg() . '.');

                return;
            }

            if (! is_array($decoded)) {
                $fail('Expected a JSON ' . ($shape === 'list' ? 'array' : 'object') . '.');

                return;
            }

            if ($shape === 'list') {
                if (! array_is_list($decoded)) {
                    $fail('Expected a JSON array, e.g. [{"' . ($requiredKeys[0] ?? 'name') . '": "…"}].');

                    return;
                }

                foreach ($decoded as $i => $entry) {
                    foreach ($requiredKeys as $key) {
                        if (! is_array($entry) || ! array_key_exists($key, $entry)) {
                            $fail('Entry ' . ($i + 1) . ' is missing "' . $key . '".');

                            return;
                        }
                    }
                }
            }
        };
    }

    public function messages(): array
    {
        return [
            'key.regex'         => 'Use the frontend route name — lowercase letters, numbers, dots and dashes only (e.g. about-us).',
            'key.unique'        => 'Another page already uses that route name.',
            'canonical_url.url' => 'Enter the full URL, including https://.',
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'key'         => strtolower(trim((string) $this->input('key'))),
            'meta_robots' => trim((string) $this->input('meta_robots')) ?: 'index, follow',
            'is_active'   => $this->boolean('is_active'),
        ]);
    }
}
