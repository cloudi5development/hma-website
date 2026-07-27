<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AdminNotification extends Model
{
    protected $fillable = ['type', 'title', 'body', 'url', 'is_read'];

    protected $casts = [
        'is_read' => 'boolean',
    ];

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
