<?php

namespace App\Http\Requests\Backend;

use App\Models\AboutSection;
use Illuminate\Foundation\Http\FormRequest;

class AboutSectionRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Route is already behind the admin.auth / admin.module middleware.
        return true;
    }

    public function rules(): array
    {
        return [
            'label'     => ['nullable', 'string', 'max:80'],
            'title'     => ['nullable', 'string', 'max:200'],
            'lead'      => ['nullable', 'string', 'max:600'],
            'image'     => ['nullable', 'image', 'mimes:webp,png,jpg,jpeg', 'max:3072'],
            'is_active' => ['nullable', 'boolean'],

            /* ---- Repeater. Rows with no title are dropped below, so every
                    field stays nullable. Colour and position are not posted at
                    all — the section assigns them from the row order. ---- */
            'items'                  => ['nullable', 'array'],
            'items.*.title'          => ['nullable', 'string', 'max:200'],
            'items.*.text'           => ['nullable', 'string', 'max:2000'],
            'items.*.zoom'           => ['nullable', 'boolean'],
            'items.*.eyes'           => ['nullable', 'boolean'],
            'items.*.image'          => ['nullable', 'image', 'mimes:webp,png,jpg,jpeg', 'max:3072'],
            'items.*.existing_image' => ['nullable', 'string', 'max:255'],
            'items.*.is_active'      => ['nullable', 'boolean'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'is_active' => $this->boolean('is_active'),
            'items'     => $this->cleanedRows(),
        ]);
    }

    /**
     * Drop the blank row an admin leaves behind after clicking "Add" and saving
     * without typing — otherwise the max: rule counts rows that are never
     * stored. Keys are kept as posted: the uploaded files live in a parallel bag
     * that is indexed by them.
     */
    private function cleanedRows(): array
    {
        $rows = $this->input('items');

        if (! is_array($rows)) {
            return [];
        }

        return array_filter($rows, fn ($row) => is_array($row) && filled($row['title'] ?? null));
    }

    /** The section being edited, resolved from the {key} route parameter. */
    public function aboutSection(): AboutSection
    {
        return AboutSection::where('key', $this->route('key'))->firstOrFail();
    }

    public function messages(): array
    {
        return [
            'image.max'         => 'The image may not be larger than 3 MB.',
            'items.*.image.max' => 'Each image may not be larger than 3 MB.',
        ];
    }
}
