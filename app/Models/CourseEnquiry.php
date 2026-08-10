<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CourseEnquiry extends Model
{
    /** Workflow statuses shared with contact enquiries. */
    public const STATUSES = ['New', 'Contacted', 'Closed'];

    protected $fillable = [
        'course_id', 'course_name', 'batch', 'name', 'email', 'phone', 'city',
        'career_goal', 'message', 'ip_address', 'status',
    ];

    protected $casts = [
        'course_id' => 'integer',
    ];

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
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
              ->orWhere('course_name', 'like', $like);
        });
    }
}
