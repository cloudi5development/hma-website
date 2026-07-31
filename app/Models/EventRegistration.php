<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EventRegistration extends Model
{
    /** Workflow statuses shared with the other two Leads lists. */
    public const STATUSES = ['New', 'Contacted', 'Closed'];

    /** The "Professional Status" dropdown on the registration modal. */
    public const PROFESSIONAL_STATUSES = [
        'Student',
        'Fresher',
        'Working Professional',
        'Freelancer',
        'Business Owner',
        'Other',
    ];

    protected $fillable = [
        'event_id', 'event_title', 'name', 'email', 'phone', 'city',
        'professional_status', 'organisation', 'agreed_terms', 'ip_address', 'status',
    ];

    protected $casts = [
        'event_id'     => 'integer',
        'agreed_terms' => 'boolean',
    ];

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    /** Free-text search across the columns the admin filters on. */
    public function scopeSearch(Builder $q, ?string $term): Builder
    {
        $term = trim((string) $term);

        if ($term === '') {
            return $q;
        }

        return $q->where(function (Builder $w) use ($term) {
            $like = '%' . $term . '%';
            $w->where('name', 'like', $like)
              ->orWhere('email', 'like', $like)
              ->orWhere('phone', 'like', $like)
              ->orWhere('event_title', 'like', $like);
        });
    }
}
