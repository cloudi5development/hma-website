<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Move every stored timestamp onto the clock the panel now reads them by.
 *
 * `config/app.php` hardcoded 'UTC' while the .env had named Asia/Kolkata all
 * along, so the app ran in UTC and wrote UTC into every `created_at`. The admin
 * panel prints those straight (`$enquiry->created_at->format('g:i a')`), which
 * is why an enquiry that arrived at 1:02 pm was listed as 7:32 am.
 *
 * Fixing the config alone only fixes what happens next: the rows already in the
 * table hold UTC wall-clock text, and would now be read as if it were IST — so
 * every enquiry from before the fix would keep its 5h30m error while everything
 * after it read correctly. A panel that is wrong for old rows and right for new
 * ones is harder to trust than one that is uniformly wrong, so the existing rows
 * are moved forward by the same offset here.
 *
 * Scope: DATETIME/TIMESTAMP columns only. The date-only columns (event_date,
 * course_schedules.start_date/end_date, blogs.published_at) are days, not
 * instants — shifting one would drag a batch onto the wrong date — and their
 * DATE type is what keeps them out of the sweep.
 *
 * MySQL only. The test suite builds a fresh database per run, so there is no
 * legacy UTC data there to correct, and INFORMATION_SCHEMA is not portable.
 */
return new class extends Migration
{
    /**
     * Tables left alone deliberately.
     *
     * A password reset token is judged by its age. Moving its created_at forward
     * would hand an expired token another 5h30m of life; leaving it behind means
     * it reads as older and simply expires, which is the safe direction for the
     * one kind of row here where the timestamp is a permission.
     */
    private const SKIP = ['password_reset_tokens'];

    public function up(): void
    {
        $this->shiftBy($this->offsetMinutes());
    }

    public function down(): void
    {
        $this->shiftBy(-$this->offsetMinutes());
    }

    /**
     * How far the app's timezone sits ahead of UTC, in minutes.
     *
     * Read from the configuration rather than written as 330, so this stays
     * correct if the app is ever pointed at another zone. Asia/Kolkata has no
     * daylight saving, so one offset covers every row; a DST zone would strictly
     * need the offset in force on each row's own date.
     */
    private function offsetMinutes(): int
    {
        $timezone = (string) config('app.timezone');

        if ($timezone === '' || $timezone === 'UTC') {
            return 0;
        }

        return (int) ((new DateTimeZone($timezone))
            ->getOffset(new DateTime('now', new DateTimeZone('UTC'))) / 60);
    }

    private function shiftBy(int $minutes): void
    {
        if ($minutes === 0 || DB::getDriverName() !== 'mysql') {
            return;
        }

        foreach ($this->timestampColumns() as $table => $columns) {
            foreach ($columns as $column) {
                DB::table($table)->whereNotNull($column)->update([
                    // $minutes is cast to int above, so it cannot carry anything
                    // but a number into the expression.
                    $column => DB::raw("DATE_ADD(`{$column}`, INTERVAL {$minutes} MINUTE)"),
                ]);
            }
        }
    }

    /**
     * Every DATETIME/TIMESTAMP column in this database, asked of the schema
     * rather than listed by hand — a table added later is covered without anyone
     * remembering to come back here.
     *
     * @return array<string, array<int, string>>
     */
    private function timestampColumns(): array
    {
        $rows = DB::select(
            "SELECT TABLE_NAME AS `table`, COLUMN_NAME AS `column`
               FROM INFORMATION_SCHEMA.COLUMNS
              WHERE TABLE_SCHEMA = ?
                AND DATA_TYPE IN ('datetime', 'timestamp')
              ORDER BY TABLE_NAME, ORDINAL_POSITION",
            [DB::getDatabaseName()],
        );

        $columns = [];

        foreach ($rows as $row) {
            if (in_array($row->table, self::SKIP, true)) {
                continue;
            }

            $columns[$row->table][] = $row->column;
        }

        return $columns;
    }
};
