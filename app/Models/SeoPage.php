<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Throwable;

/**
 * SEO for one frontend page. `key` is the frontend route name without the
 * "frontend." prefix, e.g. "about-us" for route('frontend.about-us').
 *
 * The social fields fall back to the meta title/description when left blank, so
 * a marketer only fills them in when the shared card should differ from the
 * search result.
 */
class SeoPage extends Model
{
    protected $fillable = [
        'key', 'label', 'title', 'meta_description', 'meta_keywords',
        'meta_robots', 'canonical_url',
        'og_title', 'og_description', 'og_image',
        'twitter_title', 'twitter_description', 'twitter_image',
        'schema_json', 'breadcrumb_schema', 'faq_schema',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /** Robots values offered on the form. */
    public const ROBOTS = [
        'index, follow'     => 'Index, Follow',
        'noindex, follow'   => 'No Index, Follow',
        'index, nofollow'   => 'Index, No Follow',
        'noindex, nofollow' => 'No Index, No Follow',
    ];

    /** Request-scoped cache — the layout asks for this on every page render. */
    protected static ?array $cache = null;

    /** Active rows keyed by page key. Never throws (falls back to []). */
    public static function allCached(): array
    {
        if (static::$cache !== null) {
            return static::$cache;
        }

        try {
            return static::$cache = static::where('is_active', true)->get()->keyBy('key')->all();
        } catch (Throwable $e) {
            return static::$cache = [];   // table missing / DB down → page's own meta is used
        }
    }

    /**
     * The record for a route name ("frontend.about-us" or plain "about-us"),
     * or null when the page has no row / the row is switched off.
     */
    public static function forRoute(?string $routeName): ?self
    {
        if (! $routeName) {
            return null;
        }

        $key = str_starts_with($routeName, 'frontend.')
            ? substr($routeName, strlen('frontend.'))
            : $routeName;

        return static::allCached()[$key] ?? null;
    }

    /** Forget the request cache (after an admin save). */
    public static function flushCache(): void
    {
        static::$cache = null;
    }

    public function scopeActive(Builder $q): Builder
    {
        return $q->where('is_active', true);
    }

    /* ---------------------------------------------------------------------
     | Resolved values for the document head — social falls back to the meta
     | fields, so a blank OG title still shares correctly.
     * ------------------------------------------------------------------- */

    public function getOgImageUrlAttribute(): ?string
    {
        return $this->og_image ? asset($this->og_image) : null;
    }

    public function getTwitterImageUrlAttribute(): ?string
    {
        return $this->twitter_image ? asset($this->twitter_image) : null;
    }

    public function resolvedOgTitle(?string $fallback = null): ?string
    {
        return $this->og_title ?: ($this->title ?: $fallback);
    }

    public function resolvedOgDescription(?string $fallback = null): ?string
    {
        return $this->og_description ?: ($this->meta_description ?: $fallback);
    }

    public function resolvedOgImage(?string $fallback = null): ?string
    {
        return $this->og_image_url ?: $fallback;
    }

    public function resolvedTwitterTitle(?string $fallback = null): ?string
    {
        return $this->twitter_title ?: $this->resolvedOgTitle($fallback);
    }

    public function resolvedTwitterDescription(?string $fallback = null): ?string
    {
        return $this->twitter_description ?: $this->resolvedOgDescription($fallback);
    }

    public function resolvedTwitterImage(?string $fallback = null): ?string
    {
        return $this->twitter_image_url ?: $this->resolvedOgImage($fallback);
    }

    /**
     * JSON-LD blocks to print in the head, as ready-to-encode arrays. Invalid
     * JSON is skipped rather than breaking the page — the admin form validates
     * it on save, so this only guards against data edited outside the panel.
     */
    public function schemaBlocks(): array
    {
        $blocks = [];

        if ($graph = $this->decode($this->schema_json)) {
            $blocks[] = $graph + ['@context' => 'https://schema.org'];
        }

        if ($crumbs = $this->decode($this->breadcrumb_schema)) {
            $blocks[] = [
                '@context'        => 'https://schema.org',
                '@type'           => 'BreadcrumbList',
                'itemListElement' => collect($crumbs)->values()->map(fn ($c, $i) => [
                    '@type'    => 'ListItem',
                    'position' => $i + 1,
                    'name'     => $c['name'] ?? '',
                    'item'     => isset($c['url']) ? url($c['url']) : null,
                ])->all(),
            ];
        }

        if ($faqs = $this->decode($this->faq_schema)) {
            $blocks[] = [
                '@context'   => 'https://schema.org',
                '@type'      => 'FAQPage',
                'mainEntity' => collect($faqs)->values()->map(fn ($f) => [
                    '@type'          => 'Question',
                    'name'           => $f['question'] ?? '',
                    'acceptedAnswer' => ['@type' => 'Answer', 'text' => $f['answer'] ?? ''],
                ])->all(),
            ];
        }

        return $blocks;
    }

    private function decode(?string $json): ?array
    {
        if (blank($json)) {
            return null;
        }

        $decoded = json_decode($json, true);

        return is_array($decoded) && $decoded !== [] ? $decoded : null;
    }
}
