<?php

namespace App\Support;

/**
 * What this server will actually accept on an upload.
 *
 * PHP rejects an oversized file before Laravel ever validates it, which surfaces
 * as the bare "failed to upload" message. Reading the real limits lets the forms
 * state the true maximum and lets validation fail with a message that explains
 * what to do about it.
 */
class UploadLimit
{
    /** Effective per-file ceiling in kilobytes (Laravel's `max:` unit). */
    public static function kilobytes(): int
    {
        $upload = static::toBytes(ini_get('upload_max_filesize'));
        $post   = static::toBytes(ini_get('post_max_size'));

        // post_max_size covers the whole request, so it caps the file too.
        // A 0/empty value means "unlimited" — ignore it rather than clamp to 0.
        $limits = array_filter([$upload, $post]);

        return $limits ? (int) floor(min($limits) / 1024) : PHP_INT_MAX;
    }

    /**
     * The ceiling a given form should enforce: whatever the app wants, capped
     * by what the server can physically accept.
     *
     * @param  int  $preferredKb  the limit the feature would like to allow
     */
    public static function cap(int $preferredKb): int
    {
        return min($preferredKb, static::kilobytes());
    }

    /** Human label for the effective limit, e.g. "8 MB". */
    public static function label(?int $kilobytes = null): string
    {
        $kb = $kilobytes ?? static::kilobytes();

        if ($kb >= 1024) {
            $mb = $kb / 1024;

            return rtrim(rtrim(number_format($mb, 1, '.', ''), '0'), '.') . ' MB';
        }

        return $kb . ' KB';
    }

    /** True when the server is the thing holding the limit down. */
    public static function isServerCapped(int $preferredKb): bool
    {
        return static::kilobytes() < $preferredKb;
    }

    /** "8M" / "512K" / "1G" → bytes. */
    protected static function toBytes(string|false|null $value): int
    {
        $value = trim((string) $value);

        if ($value === '') {
            return 0;
        }

        $unit   = strtolower(substr($value, -1));
        $number = (int) $value;

        return match ($unit) {
            'g'     => $number * 1024 ** 3,
            'm'     => $number * 1024 ** 2,
            'k'     => $number * 1024,
            default => $number,
        };
    }
}
