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
        return [
            'title'         => ['required', 'string', 'max:160'],
            // Capped by what PHP will actually accept, so the rule and the
            // server agree and the admin gets a message that makes sense.
            //
            // Never unconditionally required now: a reel may be a link instead
            // of a file. Which of the three is missing is settled in
            // withValidator, where they can be looked at together.
            'video'         => ['nullable', 'file', 'mimetypes:video/mp4,video/webm,video/ogg,video/quicktime', 'max:' . UploadLimit::cap(self::PREFERRED_MAX_KB)],
            'instagram_url' => ['nullable', 'url', 'max:255'],
            'youtube_url'   => ['nullable', 'url', 'max:255'],
            'sort_order'    => ['nullable', 'integer', 'min:0', 'max:9999'],
            'is_active'     => ['nullable', 'boolean'],
        ];
    }

    /**
     * A reel has to be playable: an uploaded clip, a YouTube link, or an
     * Instagram link we can actually embed.
     *
     * Each link is checked whenever it is filled in, not only when it is the one
     * that will play. An admin who pastes a broken YouTube URL next to a working
     * upload has still made a mistake, and finding out now beats finding out
     * when the upload is later removed.
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $reel = $this->route('reel');

            $hasUpload = $this->hasFile('video') || filled($reel?->video);
            $instagram = trim((string) $this->input('instagram_url'));
            $youtube   = trim((string) $this->input('youtube_url'));

            if (! $hasUpload && $instagram === '' && $youtube === '') {
                $validator->errors()->add('video',
                    'Add something to play: upload a clip, or paste a YouTube or Instagram link.');

                return;
            }

            if ($youtube !== '' && ! (new \App\Models\Reel(['youtube_url' => $youtube]))->youtubeId()) {
                $validator->errors()->add('youtube_url',
                    'That does not look like a YouTube video link. It should look like '
                    . 'https://www.youtube.com/watch?v=XXXXXXXXXXX, https://youtu.be/XXXXXXXXXXX '
                    . 'or https://www.youtube.com/shorts/XXXXXXXXXXX.');
            }

            // An Instagram link that cannot be framed is only fatal when it is
            // the only thing left to play — alongside an upload or a YouTube
            // video it is just the card's badge, and a profile link is a
            // perfectly reasonable badge.
            if ($instagram !== '') {
                $playsFromInstagram = ! $hasUpload
                    && ! (new \App\Models\Reel(['youtube_url' => $youtube]))->youtubeId();

                $code = (new \App\Models\Reel(['instagram_url' => $instagram]))->instagramCode();

                if ($playsFromInstagram && ! $code) {
                    $validator->errors()->add('instagram_url',
                        'That does not look like an Instagram reel link. It should look like '
                        . 'https://www.instagram.com/reel/XXXXXXXXX/ — or upload a video file '
                        . 'or paste a YouTube link instead.');
                }
            }
        });
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
            'youtube_url.url'   => 'Enter a full link, e.g. https://www.youtube.com/watch?v=…',
        ];
    }
}
