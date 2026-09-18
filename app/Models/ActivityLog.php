<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ActivityLog extends Model
{
    protected $fillable = ['actor', 'action', 'description'];

    /**
     * Record an admin action. The actor is taken from the logged-in admin's
     * session email (set at login), falling back to a generic label.
     */
    public static function record(string $action, string $description): void
    {
        // The column is 255 wide, and a description often quotes something an
        // admin named — a form, say, which has no length limit. A long name must
        // shorten its log line, never fail the save that is being logged.
        static::create([
            'actor'       => session('admin_email', 'Admin'),
            'action'      => \Illuminate\Support\Str::limit($action, 250),
            'description' => \Illuminate\Support\Str::limit($description, 250),
        ]);
    }
}
