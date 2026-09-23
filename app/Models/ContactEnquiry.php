<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use App\Models\Concerns\SummarisesForAdminEmail;
use Illuminate\Database\Eloquent\Model;

class ContactEnquiry extends Model
{
    /** Workflow statuses shared with course enquiries. */
    public const STATUSES = ['New', 'Contacted', 'Closed'];

    use SummarisesForAdminEmail;

    /** "subject" is a copy of "looking_for" kept for the admin list. */
    protected function adminEmailSkips(): array
    {
        return ['subject'];
    }

    protected function adminEmailLabels(): array
    {
        return ['looking_for' => 'Looking for', 'interest' => 'Interested in'];
    }

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
