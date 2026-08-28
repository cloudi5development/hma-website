<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class Reel extends Model
{
    protected $fillable = [
        'title', 'video', 'instagram_url', 'youtube_url', 'sort_order', 'is_active',
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
     * True when this reel is played from someone else's player rather than from
     * an upload.
     *
     * An upload always wins: it is served from this host, autoplays muted, and
     * costs the visitor a fraction of what a third-party player does. The embed
     * is the fallback for a reel that was added as a link with no file.
     */
    public function usesEmbed(): bool
    {
        return blank($this->video) && $this->embed_url !== null;
    }

    /**
     * Whose player this reel is framed in — 'youtube', 'instagram', or null when
     * neither link is one we recognise.
     *
     * YouTube is preferred when both are filled in. The two links do not do the
     * same job: `instagram_url` has always doubled as the card's badge, so a reel
     * carrying both is one whose video is on YouTube and whose post is on
     * Instagram. The card then plays the video and links out to the post.
     */
    public function embedProvider(): ?string
    {
        if ($this->youtubeEmbedUrl() !== null) {
            return 'youtube';
        }

        return $this->instagramEmbedUrl() !== null ? 'instagram' : null;
    }

    /** The embed URL for whichever player this reel uses, or null. */
    public function getEmbedUrlAttribute(): ?string
    {
        return $this->youtubeEmbedUrl() ?? $this->instagramEmbedUrl();
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
    public function instagramEmbedUrl(): ?string
    {
        $code = $this->instagramCode();

        return $code ? "https://www.instagram.com/reel/{$code}/embed/" : null;
    }

    /**
     * YouTube's embed URL for this reel, rebuilt from the video id alone for the
     * same reason Instagram's is — nothing pasted reaches an iframe src.
     *
     * rel=0 keeps the end screen's suggestions to the same channel, and
     * playsinline stops iOS taking the video fullscreen out of the card.
     */
    public function youtubeEmbedUrl(): ?string
    {
        $id = $this->youtubeId();

        return $id ? "https://www.youtube.com/embed/{$id}?rel=0&playsinline=1" : null;
    }

    /**
     * The video id out of a YouTube URL.
     *
     * Covers every shape a share button hands out: watch?v=, youtu.be/,
     * /shorts/, /embed/, /live/ and the old /v/. Ids are restricted to YouTube's
     * own alphabet, so a path that is really something else cannot come through.
     */
    public function youtubeId(): ?string
    {
        if (blank($this->youtube_url)) {
            return null;
        }

        $host = parse_url($this->youtube_url, PHP_URL_HOST);

        // Must actually be YouTube. Without this check any URL could be framed.
        if (! $host || ! preg_match('/(^|\.)(youtube\.com|youtu\.be|youtube-nocookie\.com)$/i', $host)) {
            return null;
        }

        $path = (string) parse_url($this->youtube_url, PHP_URL_PATH);

        // youtu.be/<id> — the id is the whole path.
        if (preg_match('/(^|\.)youtu\.be$/i', $host)) {
            return preg_match('#^/([A-Za-z0-9_-]{6,20})#', $path, $m) ? $m[1] : null;
        }

        if (preg_match('#/(?:shorts|embed|live|v)/([A-Za-z0-9_-]{6,20})#', $path, $m)) {
            return $m[1];
        }

        // watch?v=<id>, possibly alongside a playlist or a timestamp.
        parse_str((string) parse_url($this->youtube_url, PHP_URL_QUERY), $query);

        $id = $query['v'] ?? null;

        return is_string($id) && preg_match('/^[A-Za-z0-9_-]{6,20}$/', $id) ? $id : null;
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
