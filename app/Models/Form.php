<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

/**
 * One admin-built form.
 *
 * The row carries nothing about the questions — those are FormFields. What it
 * owns is the form's identity (name, title, slug), whether the public page is
 * live, and the settings that decide what happens around a submission: the
 * button text, the thank-you message, the caps, the notification.
 */
class Form extends Model
{
    public const DRAFT     = 'draft';
    public const PUBLISHED = 'published';
    public const DISABLED  = 'disabled';

    public const STATUSES = [
        self::DRAFT     => 'Draft',
        self::PUBLISHED => 'Published',
        self::DISABLED  => 'Disabled',
    ];

    /**
     * The settings JSON, and what each key means when the admin has not set it.
     *
     * Every default here is a real answer rather than a blank: a form saved
     * without touching the settings tab still has a submit button that says
     * something and a message that thanks the person who filled it in.
     */
    public const SETTING_DEFAULTS = [
        'submit_label'     => 'Submit',
        'success_message'  => 'Thank you! Your response has been submitted successfully.',
        'redirect_url'     => null,
        'allow_multiple'   => true,
        'max_submissions'  => null,
        'closed_message'   => 'This form is currently closed.',
        'notify_enabled'   => false,
        'notify_emails'    => null,
        'notify_subject'   => null,
    ];

    protected $fillable = ['name', 'title', 'description', 'slug', 'status', 'settings'];

    protected $casts = [
        'settings' => 'array',
    ];

    protected static function booted(): void
    {
        static::saving(function (Form $form) {
            if (blank($form->slug)) {
                $form->slug = static::uniqueSlug($form->name ?: $form->title, $form->id);
            }
        });
    }

    /* ============================== RELATIONS ============================== */

    /** The live questions, in display order. Soft-deleted ones are excluded. */
    public function fields(): HasMany
    {
        return $this->hasMany(FormField::class)->orderBy('sort_order')->orderBy('id');
    }

    /**
     * Questions including the removed ones — needed when reading old responses,
     * which may reference a field the admin has since deleted.
     */
    public function allFields(): HasMany
    {
        return $this->hasMany(FormField::class)->withTrashed()->orderBy('sort_order')->orderBy('id');
    }

    public function responses(): HasMany
    {
        return $this->hasMany(FormResponse::class)->latest('submitted_at')->latest('id');
    }

    /* ================================ SCOPES =============================== */

    public function scopePublished(Builder $q): Builder
    {
        return $q->where('status', self::PUBLISHED);
    }

    /* =============================== SETTINGS ============================== */

    /** One setting, falling back to the documented default. */
    public function setting(string $key): mixed
    {
        $value = $this->settings[$key] ?? null;

        // '' is what an emptied text input posts, and it means "use the default"
        // rather than "show nothing" for every setting here.
        return ($value === null || $value === '') ? (self::SETTING_DEFAULTS[$key] ?? null) : $value;
    }

    public function getSubmitLabelAttribute(): string
    {
        return (string) $this->setting('submit_label');
    }

    public function getSuccessMessageAttribute(): string
    {
        return (string) $this->setting('success_message');
    }

    public function getClosedMessageAttribute(): string
    {
        return (string) $this->setting('closed_message');
    }

    public function getRedirectUrlAttribute(): ?string
    {
        return $this->setting('redirect_url');
    }

    public function getAllowsMultipleAttribute(): bool
    {
        return (bool) $this->setting('allow_multiple');
    }

    public function getMaxSubmissionsAttribute(): ?int
    {
        $max = $this->setting('max_submissions');

        return $max ? (int) $max : null;
    }

    /**
     * Where a submission notification is sent. Blank when notifications are off
     * or no address was given, and the caller then sends nothing.
     *
     * @return array<int, string>
     */
    public function notificationRecipients(): array
    {
        if (! $this->setting('notify_enabled')) {
            return [];
        }

        return collect(preg_split('/[,;\s]+/', (string) $this->setting('notify_emails')))
            ->map(fn ($email) => trim($email))
            ->filter(fn ($email) => filter_var($email, FILTER_VALIDATE_EMAIL))
            ->unique()
            ->values()
            ->all();
    }

    public function getNotificationSubjectAttribute(): string
    {
        return (string) ($this->setting('notify_subject') ?: 'New response — ' . $this->title);
    }

    /* ================================ STATE ================================ */

    public function isPublished(): bool
    {
        return $this->status === self::PUBLISHED;
    }

    /** How many more submissions the cap allows, or null when uncapped. */
    public function remainingSubmissions(): ?int
    {
        $max = $this->max_submissions;

        return $max === null ? null : max(0, $max - $this->responses()->count());
    }

    /**
     * Whether the public page will take a submission right now, and why not.
     *
     * One method rather than a scatter of checks, because the page and the POST
     * handler have to agree — a form that renders a submit button and then
     * refuses the submission is the worst of both.
     *
     * @return array{0: bool, 1: ?string}  [accepting, reason when it is not]
     */
    public function submissionState(): array
    {
        if (! $this->isPublished()) {
            return [false, $this->closed_message];
        }

        if ($this->remainingSubmissions() === 0) {
            return [false, $this->closed_message];
        }

        return [true, null];
    }

    public function getStatusLabelAttribute(): string
    {
        return self::STATUSES[$this->status] ?? $this->status;
    }

    /* ================================= URLS ================================ */

    public function getPublicUrlAttribute(): string
    {
        return route('frontend.form.show', $this->slug);
    }

    public function getEmbedCodeAttribute(): string
    {
        return '<iframe src="' . e($this->public_url) . '?embed=1" width="100%" height="700" '
            . 'frameborder="0" style="border:0;max-width:100%" title="' . e($this->title) . '"></iframe>';
    }

    /* ================================ SLUGS ================================ */

    /**
     * A slug that no other form is using.
     *
     * Falls back to "form" for a name that slugs to nothing at all (a title in a
     * non-Latin script, say), then counts up until the slug is free.
     */
    public static function uniqueSlug(?string $source, ?int $ignoreId = null): string
    {
        $base = Str::slug((string) $source) ?: 'form';
        $slug = $base;
        $n    = 1;

        while (static::query()
            ->where('slug', $slug)
            ->when($ignoreId, fn ($q) => $q->whereKeyNot($ignoreId))
            ->exists()) {
            $slug = $base . '-' . ++$n;
        }

        return $slug;
    }
}
