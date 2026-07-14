<?php

use App\Support\ApiResponse;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/*
|--------------------------------------------------------------------------
| Global Helper Functions
|--------------------------------------------------------------------------
|
| Autoloaded via composer.json ("autoload.files"). Keep these generic and
| dependency-free — anything tied to a specific model/service belongs in that
| service, not here.
|
*/

if (! function_exists('responseSuccess')) {
    /**
     * Shortcut for a standardized success JSON response.
     */
    function responseSuccess(mixed $data = null, string $message = 'Success', int $code = 200, array $meta = [])
    {
        return ApiResponse::success($data, $message, $code, $meta);
    }
}

if (! function_exists('responseError')) {
    /**
     * Shortcut for a standardized error JSON response.
     */
    function responseError(string $message = 'Something went wrong', mixed $errors = null, int $code = 400, array $meta = [])
    {
        return ApiResponse::error($message, $errors, $code, $meta);
    }
}

if (! function_exists('formatDate')) {
    /**
     * Safely format a date value, returning null for empty input.
     */
    function formatDate(mixed $date, string $format = 'd M Y'): ?string
    {
        return $date ? Carbon::parse($date)->format($format) : null;
    }
}

if (! function_exists('generateSlug')) {
    /**
     * Generate a URL slug from a string. When $table is given, guarantees
     * uniqueness by appending -1, -2, ... (optionally ignoring $ignoreId, e.g.
     * the current record on update).
     */
    function generateSlug(string $value, ?string $table = null, string $column = 'slug', ?int $ignoreId = null): string
    {
        $slug = Str::slug($value);

        if (! $table) {
            return $slug;
        }

        $original = $slug;
        $count = 1;

        while (DB::table($table)
            ->where($column, $slug)
            ->when($ignoreId, fn ($query) => $query->where('id', '!=', $ignoreId))
            ->exists()) {
            $slug = "{$original}-{$count}";
            $count++;
        }

        return $slug;
    }
}
