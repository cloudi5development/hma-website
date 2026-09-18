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
 * button text, the thank-you message, the notification.
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

    /* ============================== STRUCTURE ==============================
       What shape this form is. Four shapes, one set of tables: a question's
       page and section are both nullable, and which of them is filled in is the
       whole difference between them. See the structure migration.

       A form built before pages existed reads as PLAIN by the column default,
       which is exactly what it is. */

    public const PLAIN          = 'plain';
    public const SECTIONS       = 'sections';
    public const PAGES          = 'pages';
    public const PAGES_SECTIONS = 'pages_sections';

    /* ============================== FORM TYPE ==============================
       A quiz is a standard form whose Multiple choice questions may carry a
       correct answer. Nothing is scored — the answer is stored so a response
       can later be compared against it. See the form_type migration. */

    public const STANDARD = 'standard';
    public const QUIZ     = 'quiz';

    public const FORM_TYPES = [
        self::STANDARD => [
            'label' => 'Standard Form',
            'hint'  => 'Collect answers. Nothing is right or wrong.',
        ],
        self::QUIZ => [
            'label' => 'Quiz / MCQ',
            'hint'  => 'Multiple choice questions can have a correct answer.',
        ],
    ];

    /** Label and one line of explanation for each, as the builder's cards show them. */
    public const STRUCTURES = [
        self::PLAIN => [
            'label' => 'Plain Form',
            'hint'  => 'One page, one list of questions.',
        ],
        self::SECTIONS => [
            'label' => 'Sections',
            'hint'  => 'One page, questions grouped under headings.',
        ],
        self::PAGES => [
            'label' => 'Multi-Page',
            'hint'  => 'A step at a time, with Next and Back.',
        ],
        self::PAGES_SECTIONS => [
            'label' => 'Multi-Page + Sections',
            'hint'  => 'Steps, each grouped under headings.',
        ],
    ];

    /**
     * The settings JSON, and what each key means when the admin has not set it.
     *
     * Every default here is a real answer rather than a blank: a form saved
     * without touching the settings tab still has a submit button that says
     * something and a message that thanks the person who filled it in.
     *
     * There is no submission cap and no "one response per person" any more —
     * both were removed along with every other limit on the module. A form
     * that is published takes every response it is sent. Values of either key
     * still stored on an older form are simply not read.
     */
    public const SETTING_DEFAULTS = [
        'submit_label'     => 'Submit',
        'success_message'  => 'Thank you! Your response has been submitted successfully.',
        'redirect_url'     => null,
        'closed_message'   => 'This form is currently closed.',
        'notify_enabled'   => false,
        'notify_emails'    => null,
        'notify_subject'   => null,
    ];

    protected $fillable = ['name', 'title', 'description', 'slug', 'structure_type', 'form_type', 'status', 'settings'];

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

    /** The form's steps, in order. Empty on a single-page form. */
    public function pages(): HasMany
    {
        return $this->hasMany(FormPage::class)->orderBy('sort_order')->orderBy('id');
    }

    /**
     * Every section on the form, whichever page it belongs to.
     *
     * Loaded flat and filtered per page by FormLayout rather than through
     * pages.sections, so a single-page section form (whose sections hang off no
     * page at all) and a multi-page one are the same one query.
     */
    public function sections(): HasMany
    {
        return $this->hasMany(FormSection::class)->orderBy('sort_order')->orderBy('id');
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

    /* ============================== STRUCTURE ============================== */

    /** Anything unrecognised reads as plain, which is the shape that always works. */
    public function structure(): string
    {
        return array_key_exists((string) $this->structure_type, self::STRUCTURES)
            ? $this->structure_type
            : self::PLAIN;
    }

    public function hasPages(): bool
    {
        return in_array($this->structure(), [self::PAGES, self::PAGES_SECTIONS], true);
    }

    public function hasSections(): bool
    {
        return in_array($this->structure(), [self::SECTIONS, self::PAGES_SECTIONS], true);
    }

    /** Anything unrecognised reads as standard, which asks nothing extra of anyone. */
    public function isQuiz(): bool
    {
        return $this->form_type === self::QUIZ;
    }

    public function getStructureLabelAttribute(): string
    {
        return self::STRUCTURES[$this->structure()]['label'];
    }

    /** The form arranged as pages → sections → questions. See FormLayout. */
    public function layout(): array
    {
        return \App\Support\FormLayout::for($this);
    }

    /**
     * Whether the public page will take a submission right now, and why not.
     *
     * One method rather than a scatter of checks, because the page and the POST
     * handler have to agree — a form that renders a submit button and then
     * refuses the submission is the worst of both.
     *
     * Being published is the only condition. There is no cap on how many
     * responses a form takes, and nobody is turned away for having answered
     * before.
     *
     * @return array{0: bool, 1: ?string}  [accepting, reason when it is not]
     */
    public function submissionState(): array
    {
        return $this->isPublished() ? [true, null] : [false, $this->closed_message];
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
        // A form's name has no length limit, but its link lives in a 255-wide
        // unique column and has to stay something a person can share — so a
        // long name makes a link from its opening words. The name is untouched.
        $base = trim(Str::limit(Str::slug((string) $source), 180, ''), '-') ?: 'form';
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
