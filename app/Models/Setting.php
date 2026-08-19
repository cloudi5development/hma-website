<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Throwable;

class Setting extends Model
{
    protected $fillable = ['key', 'value'];

    /** Request-scoped cache so many get() calls hit the DB only once. */
    protected static ?array $cache = null;

    /** All settings as [key => value]. Never throws (falls back to []). */
    public static function allCached(): array
    {
        if (static::$cache !== null) {
            return static::$cache;
        }

        try {
            return static::$cache = static::pluck('value', 'key')->all();
        } catch (Throwable $e) {
            return static::$cache = [];   // table missing / DB down → fall back to .env
        }
    }

    /** Get one setting, with a default. */
    public static function get(string $key, mixed $default = null): mixed
    {
        $value = static::allCached()[$key] ?? null;

        return ($value === null || $value === '') ? $default : $value;
    }

    /**
     * Public URL for an uploaded image setting (logo, favicon …), falling back
     * to the asset bundled with the theme when nothing has been uploaded.
     */
    public static function image(string $key, string $fallback): string
    {
        return asset(static::get($key) ?: $fallback);
    }

    /**
     * Contact details for the frontend, in one shape the footer, the contact
     * form and the branch map can all read. Defaults match the placeholders on
     * Settings → Contact, so an untouched install shows the same details it
     * always did.
     */
    public static function contactDetails(): array
    {
        $phone = static::get('contact_phone', '+91 78240 94044');
        $email = static::get('contact_email', 'info@hiremindsacademy.com');

        $branches = static::branches();

        return [
            'phone'      => $phone,
            // tel: needs digits only (a leading + is allowed) — spaces break dialling.
            'phone_href' => 'tel:' . preg_replace('/[^\d+]/', '', $phone),
            'email'      => $email,
            'email_href' => 'mailto:' . $email,
            'branches'   => $branches,
        ];
    }

    /**
     * Branch offices, each with its own name, address and map. Every entry gets
     * a `key` (slug, used by the contact page's tab switcher) and a `map` that
     * is always embeddable — an admin who leaves the embed field blank falls
     * back to a map generated from the address.
     *
     * @return array<int, array{key:string,name:string,address:string,map:string}>
     */
    public static function branches(): array
    {
        $stored = json_decode((string) static::get('contact_branches'), true);

        if (! is_array($stored) || $stored === []) {
            $stored = static::legacyBranches();
        }

        $out = [];

        foreach ($stored as $row) {
            $name    = trim((string) ($row['name'] ?? ''));
            $address = trim((string) ($row['address'] ?? ''));

            if ($name === '' && $address === '') {
                continue;
            }

            $out[] = [
                'key'     => Str::slug($name) ?: 'branch-' . (count($out) + 1),
                'name'    => $name ?: 'Branch ' . (count($out) + 1),
                'address' => $address,
                'map'     => static::normaliseMapUrl($row['map'] ?? null) ?: static::mapEmbed($address),
            ];
        }

        return $out;
    }

    /** The two fixed addresses this module used before branches were repeatable. */
    protected static function legacyBranches(): array
    {
        return [
            [
                'name'    => 'Chennai',
                'address' => static::get('contact_address_chennai', 'No 22 / 97, KGEYES VEDA RANGA NIVAS 4th Floor, 4th Avenue, Ashok Nagar, Chennai – 33'),
            ],
            [
                'name'    => 'Coimbatore',
                'address' => static::get('contact_address_coimbatore', '339, Chinnasamy Naidu Rd, Siddhapudur, Balasundaram Layout, B.K.R Nagar, Coimbatore, Tamil Nadu 641044'),
            ],
        ];
    }

    /**
     * Turn whatever the admin pasted into a URL that can be framed: the whole
     * `<iframe …>` snippet Google's "Embed a map" gives you, or a bare URL.
     * Anything that is not a Google Maps address is rejected, so the field can
     * never be used to frame a third-party page.
     */
    public static function normaliseMapUrl(?string $input): ?string
    {
        $input = trim((string) $input);

        if ($input === '') {
            return null;
        }

        // Pull the src out of a pasted <iframe …> tag.
        if (preg_match('/src\s*=\s*["\']([^"\']+)["\']/i', $input, $m)) {
            $input = $m[1];
        }

        $input = html_entity_decode($input, ENT_QUOTES);

        $host = parse_url($input, PHP_URL_HOST);

        if (! $host || ! preg_match('/(^|\.)google\.[a-z.]+$/i', $host)) {
            return null;
        }

        return $input;
    }

    /**
     * Platforms offered on Settings → Social Media, in the order they render.
     *
     * This list is the single source for both the admin form and the footer
     * icons, so dropping a platform here removes its field and its icon together
     * — which is how X/Twitter left on 2026-08-19. A `social_x` row may still
     * sit in the settings table on an older install; nothing reads it.
     */
    public const SOCIAL_PLATFORMS = [
        'social_facebook'  => ['label' => 'Facebook',  'icon' => 'fa-brands fa-facebook-f'],
        'social_instagram' => ['label' => 'Instagram', 'icon' => 'fa-brands fa-instagram'],
        'social_linkedin'  => ['label' => 'LinkedIn',  'icon' => 'fa-brands fa-linkedin-in'],
        'social_youtube'   => ['label' => 'YouTube',   'icon' => 'fa-brands fa-youtube'],
        'social_whatsapp'  => ['label' => 'WhatsApp',  'icon' => 'fa-brands fa-whatsapp'],
    ];

    /**
     * Social profiles that actually have a link, ready to render as icons.
     * A platform left blank in Settings simply doesn't appear on the site.
     *
     * @return array<int, array{key:string,label:string,icon:string,url:string}>
     */
    public static function socialLinks(): array
    {
        $links = [];

        foreach (static::SOCIAL_PLATFORMS as $key => $meta) {
            $url = trim((string) static::get($key));

            if ($url === '') {
                continue;
            }

            if ($key === 'social_whatsapp') {
                $url = static::whatsAppUrl($url);
            } elseif (! preg_match('#^https?://#i', $url)) {
                $url = 'https://' . ltrim($url, '/');
            }

            $links[] = ['key' => $key, 'label' => $meta['label'], 'icon' => $meta['icon'], 'url' => $url];
        }

        return $links;
    }

    /** WhatsApp accepts a bare number or a full link — normalise both to wa.me. */
    protected static function whatsAppUrl(string $value): string
    {
        if (preg_match('#^https?://#i', $value)) {
            return $value;
        }

        return 'https://wa.me/' . preg_replace('/\D/', '', $value);
    }

    /** Embeddable Google Maps URL for an address (?output=embed frames cleanly). */
    public static function mapEmbed(string $address): string
    {
        return 'https://www.google.com/maps?q=' . urlencode($address) . '&output=embed';
    }

    /** Persist a batch of settings and reset the request cache. */
    public static function putMany(array $pairs): void
    {
        foreach ($pairs as $key => $value) {
            static::updateOrCreate(['key' => $key], ['value' => $value]);
        }

        static::$cache = null;
    }
}
