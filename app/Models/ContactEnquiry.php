<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class ContactEnquiry extends Model
{
    /** Workflow statuses shared with course enquiries. */
    public const STATUSES = ['New', 'Contacted', 'Closed'];

    protected $fillable = [
        'name', 'email', 'phone', 'subject', 'looking_for', 'interest',
        'message', 'ip_address', 'status', 'is_read',
    ];

    protected $casts = [
        'is_read' => 'boolean',
    ];

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
              ->orWhere('phone', 'like', $like);
        });
    }
}
