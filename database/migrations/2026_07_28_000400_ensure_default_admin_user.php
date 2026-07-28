<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

/**
 * Safety net for the login switch in 000300.
 *
 * That migration only seeded the admin when the users table was empty, but a
 * fresh Laravel install already ships a `test@example.com` factory row — so on
 * those databases no admin account was created and nobody could sign in.
 *
 * Here we make sure the account exists, and switch off Laravel's sample user,
 * which would otherwise be a working admin login with the well-known factory
 * password. Its row is kept (deactivated) rather than deleted.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! DB::table('users')->where('email', 'admin@gmail.com')->exists()) {
            DB::table('users')->insert([
                'name'       => 'HireMinds Admin',
                'email'      => 'admin@gmail.com',
                'password'   => Hash::make('12345678'),
                'is_active'  => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        DB::table('users')
            ->where('email', 'test@example.com')
            ->update(['is_active' => false, 'updated_at' => now()]);
    }

    public function down(): void
    {
        // Nothing to undo — removing the only admin would lock the panel.
    }
};
