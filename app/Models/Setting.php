<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Throwable;

class Setting extends Model
{
    protected $fillable = ['key', 'value'];

    /** Request-scoped cache so many get() calls hit the DB only once. */
    protected static ?array $cache = null;

    /** All settings as [key => value]. Never throws (falls back to []). */
    public static function allCached(): array
    {
        if (static::$cache !== null) {
            return static::$cache;
        }

        try {
            return static::$cache = static::pluck('value', 'key')->all();
        } catch (Throwable $e) {
            return static::$cache = [];   // table missing / DB down → fall back to .env
        }
    }

    /** Get one setting, with a default. */
    public static function get(string $key, mixed $default = null): mixed
    {
        $value = static::allCached()[$key] ?? null;

        return ($value === null || $value === '') ? $default : $value;
    }

    /** Persist a batch of settings and reset the request cache. */
    public static function putMany(array $pairs): void
    {
        foreach ($pairs as $key => $value) {
            static::updateOrCreate(['key' => $key], ['value' => $value]);
        }

        static::$cache = null;
    }
}
