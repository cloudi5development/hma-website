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
        static::create([
            'actor'       => session('admin_email', 'Admin'),
            'action'      => $action,
            'description' => $description,
        ]);
    }
}
