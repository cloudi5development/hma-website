<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class AdminNotification extends Model
{
    protected $fillable = ['type', 'title', 'body', 'url', 'is_read', 'read_at'];

    protected $casts = [
        'is_read' => 'boolean',
        'read_at' => 'datetime',
    ];

    /** Not opened yet — the Unread tab in the topbar bell. */
    public function scopeUnread(Builder $q): Builder
    {
        return $q->where('is_read', false);
    }

    /** Already opened — the Read tab. */
    public function scopeRead(Builder $q): Builder
    {
        return $q->where('is_read', true);
    }

    /** Mark as read, recording when, and leave an already-read row alone. */
    public function markRead(): void
    {
        if ($this->is_read) {
            return;
        }

        $this->forceFill(['is_read' => true, 'read_at' => now()])->save();
    }

    /** Raise a notification for a new enquiry. */
    public static function raise(string $type, string $title, ?string $body = null, ?string $url = null): void
    {
        static::create([
            'type'  => $type,
            'title' => $title,
            'body'  => $body,
            'url'   => $url,
            'is_read' => false,
        ]);
    }
}
