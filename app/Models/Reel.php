<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class Reel extends Model
{
    protected $fillable = [
        'title', 'video', 'instagram_url', 'sort_order', 'is_active',
    ];

    protected $casts = [
        'is_active'  => 'boolean',
        'sort_order' => 'integer',
    ];

    /** Active reels, in display order. */
    public function scopeActive(Builder $q): Builder
    {
        return $q->where('is_active', true)->orderBy('sort_order')->orderBy('id');
    }

    /** Public URL for the uploaded clip, or null on a link-only reel. */
    public function getVideoUrlAttribute(): ?string
    {
        return $this->video ? asset($this->video) : null;
    }

    /**
     * True when this reel is played from Instagram rather than from an upload.
     *
     * An upload always wins: it is served from this host, autoplays muted, and
     * costs the visitor a fraction of what Instagram's player does. The embed is
     * the fallback for a reel that was added as a link with no file.
     */
    public function usesEmbed(): bool
    {
        return blank($this->video) && $this->embed_url !== null;
    }

    /**
     * Instagram's embed URL for this reel, or null when the link is not one we
     * recognise.
     *
     * Only the shortcode is taken from whatever was pasted — a full post URL, a
     * share link with tracking parameters, with or without the trailing slash —
     * and a clean embed URL is rebuilt from it. That way nothing an admin pastes
     * is passed through into an iframe src unchecked.
     */
    public function getEmbedUrlAttribute(): ?string
    {
        $code = $this->instagramCode();

        return $code ? "https://www.instagram.com/reel/{$code}/embed/" : null;
    }

    /**
     * The shortcode out of an Instagram reel/post URL.
     *
     * Accepts /reel/, /reels/, /p/ and /tv/, which are the four forms Instagram
     * has used for the same thing.
     */
    public function instagramCode(): ?string
    {
        if (blank($this->instagram_url)) {
            return null;
        }

        $host = parse_url($this->instagram_url, PHP_URL_HOST);

        // Must actually be an Instagram link. Without this check any URL could
        // be framed on the page.
        if (! $host || ! preg_match('/(^|\.)instagram\.com$/i', $host)) {
            return null;
        }

        $path = (string) parse_url($this->instagram_url, PHP_URL_PATH);

        return preg_match('#/(?:reels?|p|tv)/([A-Za-z0-9_-]+)#', $path, $m) ? $m[1] : null;
    }
}
