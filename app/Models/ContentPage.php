<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/**
 * A standalone written page — Terms & Conditions, Privacy Policy.
 *
 * The set is fixed: PAGES is the whole module, and both the admin menu and the
 * public routes are built from it. The panel edits these rows, it never creates
 * or deletes them, which is what keeps /terms-conditions and /privacy-policy
 * stable for the footer to link at.
 */
class ContentPage extends Model
{
    /** key => the route path it is served at. Also the admin menu, in order. */
    public const PAGES = [
        'terms-conditions' => 'terms-conditions',
        'privacy-policy'   => 'privacy-policy',
    ];

    protected $fillable = [
        'key', 'title', 'content', 'seo_title', 'seo_description', 'seo_keywords', 'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /** Published pages only — the frontend and the footer both go through this. */
    public function scopeActive(Builder $q): Builder
    {
        return $q->where('is_active', true);
    }

    /**
     * The page for a key, or null. Never creates one: a key that is not in PAGES
     * has no route and no menu entry, so silently making a row for it would
     * leave an orphan nobody can reach.
     */
    public static function forKey(string $key): ?self
    {
        return array_key_exists($key, static::PAGES)
            ? static::where('key', $key)->first()
            : null;
    }

    /** Its public address. */
    public function getUrlAttribute(): string
    {
        return url('/' . (static::PAGES[$this->key] ?? $this->key));
    }

    /**
     * The body with an id on every top-level heading, plus the list of those
     * headings for the page's "On this page" rail.
     *
     * Both come out of one pass on purpose: generating the ids and the links
     * separately would let them drift the moment a heading is renamed, and every
     * anchor would quietly stop working.
     *
     * @return array{html: string, sections: array<int, array{id: string, text: string}>}
     */
    public function renderedContent(): array
    {
        $html = trim((string) $this->content);

        if ($html === '') {
            return ['html' => '', 'sections' => []];
        }

        $dom = new \DOMDocument();

        // Admin-authored markup is not guaranteed to be well formed, and a
        // stray tag must not surface as a PHP warning on a public page.
        $previous = libxml_use_internal_errors(true);

        // A meta charset rather than an XML prolog: a literal "<?" is a hazard in
        // this codebase (short_open_tag is on for the live host). NOIMPLIED and
        // NODEFDTD keep DOMDocument from wrapping the fragment in html/body.
        $dom->loadHTML(
            '<meta http-equiv="Content-Type" content="text/html; charset=utf-8">'
                . '<div id="hm-content-root">' . $html . '</div>',
            LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD
        );

        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        $root = $dom->getElementById('hm-content-root');

        if (! $root) {
            return ['html' => $html, 'sections' => []];
        }

        $sections = [];
        $used = [];

        foreach ($dom->getElementsByTagName('h2') as $heading) {
            $text = trim($heading->textContent);

            if ($text === '') {
                continue;
            }

            // Two sections can legitimately share a name ("Contact" under two
            // parts); the suffix keeps every anchor unique.
            $base = Str::slug($text) ?: 'section';
            $id = $base;
            $n = 2;

            while (in_array($id, $used, true)) {
                $id = $base . '-' . $n++;
            }

            $used[] = $id;
            $heading->setAttribute('id', $id);
            $sections[] = ['id' => $id, 'text' => $text];
        }

        $out = '';

        foreach ($root->childNodes as $child) {
            $out .= $dom->saveHTML($child);
        }

        return ['html' => $out, 'sections' => $sections];
    }
}
