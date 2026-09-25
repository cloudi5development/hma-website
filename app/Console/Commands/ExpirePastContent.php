<?php

namespace App\Console\Commands;

use App\Support\ContentExpiry;
use Illuminate\Console\Command;

/**
 * Switch off events and course batches whose start date has passed.
 *
 * Scheduled daily in routes/console.php. The first web request of each day runs
 * the same sweep, so the site stays right even without the scheduler's cron.
 *
 *   php artisan content:expire-past
 */
class ExpirePastContent extends Command
{
    protected $signature = 'content:expire-past';

    protected $description = 'Deactivate events and course schedules whose start date has passed';

    public function handle(): int
    {
        $expired = ContentExpiry::run();

        $this->info("Deactivated {$expired['events']} event(s) and {$expired['schedules']} schedule(s).");

        return self::SUCCESS;
    }
}
