<?php

namespace App\Support;

use App\Models\CourseSchedule;
use App\Models\Event;
use Illuminate\Support\Facades\Cache;

/**
 * Switches off events and course batches once their start date has passed.
 *
 * Runs two ways, so the site is right whether or not the server has the
 * scheduler's cron entry:
 *
 *  - daily from the scheduler (php artisan content:expire-past, routes/console.php);
 *  - on the first web request of each day (App\Http\Middleware\ExpirePastContent),
 *    through runOncePerDay(), which a cache key keeps to a single sweep a day.
 */
class ContentExpiry
{
    /** @return array{events: int, schedules: int} rows switched off */
    public static function run(): array
    {
        return [
            'events'    => Event::expirePast(),
            'schedules' => CourseSchedule::expirePast(),
        ];
    }

    public static function runOncePerDay(): void
    {
        $key = 'content-expiry:' . now()->toDateString();

        // add() only succeeds for the first caller of the day.
        if (Cache::add($key, true, now()->endOfDay())) {
            static::run();
        }
    }
}
