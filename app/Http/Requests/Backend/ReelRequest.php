<?php

namespace App\Http\Requests\Backend;

use App\Support\UploadLimit;
use Illuminate\Foundation\Http\FormRequest;

class ReelRequest extends FormRequest
{
    /**
     * What the feature would like to allow, before the server has its say.
     * Laravel's `max:` is in kilobytes, so 40 MB is 40 × 1024 — not 40000,
     * which would really be 39.1 MB.
     */
    public const PREFERRED_MAX_KB = 40 * 1024;

    public function authorize(): bool
    {
        // Route is already behind the admin.auth middleware.
        return true;
    }

    public function rules(): array
    {
        // On create the video is required; on update it is optional (keep existing).
        $creating = $this->isMethod('post');

        return [
            'title'         => ['required', 'string', 'max:160'],
            // Capped by what PHP will actually accept, so the rule and the
            // server agree and the admin gets a message that makes sense.
            'video'         => [$creating ? 'required' : 'nullable', 'file', 'mimetypes:video/mp4,video/webm,video/ogg,video/quicktime', 'max:' . UploadLimit::cap(self::PREFERRED_MAX_KB)],
            'instagram_url' => ['nullable', 'url', 'max:255'],
            'sort_order'    => ['nullable', 'integer', 'min:0', 'max:9999'],
            'is_active'     => ['nullable', 'boolean'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'is_active'  => $this->boolean('is_active'),
            'sort_order' => $this->input('sort_order', 0),
        ]);
    }

    public function messages(): array
    {
        $max = UploadLimit::label(UploadLimit::cap(self::PREFERRED_MAX_KB));

        // "uploaded" is what fires when PHP itself refused the file — nearly
        // always because it exceeded upload_max_filesize. The default wording
        // ("failed to upload") tells the admin nothing actionable.
        $refused = UploadLimit::isServerCapped(self::PREFERRED_MAX_KB)
            ? "The video could not be uploaded. This server only accepts files up to {$max} — upload a smaller clip, or ask your host to raise upload_max_filesize and post_max_size."
            : "The video could not be uploaded. Check that it is under {$max} and try again.";

        return [
            'video.required'    => 'Please upload the reel video.',
            'video.mimetypes'   => 'The video must be an MP4, WebM, OGG or MOV file.',
            'video.max'         => "The video may not be larger than {$max}.",
            'video.uploaded'    => $refused,
            'instagram_url.url' => 'Enter a full link, e.g. https://www.instagram.com/reel/…',
        ];
    }
}
