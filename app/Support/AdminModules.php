<?php

namespace App\Support;

/**
 * The list of admin-panel modules a user account can be given access to.
 *
 * Each key is the admin route-name prefix the module owns — module "blogs"
 * covers every route named "backend.blogs.*". That is what EnsureModuleAccess
 * matches an incoming request against, so adding a module to a group below is
 * all it takes to make it both grantable on the user form and guarded on the
 * way in. Nothing else needs a list of modules.
 *
 * Groups mirror the sidebar headings, so the permission grid on the user form
 * reads the same way as the menu the user will end up with.
 */
class AdminModules
{
    public const GROUPS = [
        'Courses' => [
            'departments' => 'Departments',
            'categories'  => 'Categories',
            'courses'     => 'Courses',
        ],
        'Website Content' => [
            'hero'            => 'Hero Section',
            'partners'        => 'Trusted Partners',
            'counters'        => 'Counters',
            'events'          => 'Upcoming Events',
            'success-stories' => 'Success Stories',
            'reels'           => 'Our Journey',
            'testimonials'    => 'Testimonials',
            'faqs'            => 'FAQ',
            'about-sections'  => 'About Us',
            'blogs'           => 'Blog',
        ],
        'Leads' => [
            'course-enquiries'    => 'Course Enquiry',
            'contact-enquiries'   => 'Contact Enquiry',
            'event-registrations' => 'Event Registration',
        ],
        // The written pages (Terms, Privacy). One key covers both, the way
        // 'settings' covers every Settings tab — they are edited together and
        // there is no reason to grant one without the other.
        'Content Management' => [
            'content-pages' => 'Terms & Privacy Pages',
        ],
        'System' => [
            'settings'  => 'Settings',
            'seo-pages' => 'Page SEO',
        ],
    ];

    /**
     * Modules reserved for the main admin. Deliberately NOT grantable: managing
     * accounts and handing out access stays with the one account that owns the
     * panel, so a sub-admin with every module ticked still cannot create users.
     */
    public const SUPER_ADMIN_ONLY = ['users'];

    /** Every grantable module key, flat. */
    public static function keys(): array
    {
        return array_keys(static::labels());
    }

    /** Grantable modules as key => label, flat. */
    public static function labels(): array
    {
        return array_merge(...array_values(static::GROUPS));
    }

    public static function label(string $key): string
    {
        return static::labels()[$key] ?? $key;
    }

    public static function isGrantable(string $key): bool
    {
        return array_key_exists($key, static::labels());
    }

    public static function isSuperAdminOnly(string $key): bool
    {
        return in_array($key, static::SUPER_ADMIN_ONLY, true);
    }

    /**
     * The module a route name belongs to, or null when the route is not gated
     * (the dashboard, the notification bell, login/logout).
     *
     * "backend.settings.general" → "settings", "backend.blogs.edit" → "blogs".
     */
    public static function forRoute(?string $routeName): ?string
    {
        if (! $routeName || ! str_starts_with($routeName, 'backend.')) {
            return null;
        }

        $key = strtok(substr($routeName, strlen('backend.')), '.');

        return (static::isGrantable($key) || static::isSuperAdminOnly($key)) ? $key : null;
    }
}
