<?php

use App\Support\AdminModules;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Role + per-module access for admin accounts.
 *
 * `is_super_admin` marks the one main admin: the account that owns the panel and
 * is alone allowed to create users and hand out module access. It is not
 * fillable on the model, so nothing but this migration can set it.
 *
 * `modules` holds the module keys a normal account may open (see
 * Support\AdminModules). Null / empty means "dashboard only".
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('is_super_admin')->default(false)->after('is_active');
            $table->json('modules')->nullable()->after('is_super_admin');
        });

        // The main admin: the seeded account if it is still there, otherwise the
        // oldest account — so an existing install always ends up with exactly one
        // owner and nobody is locked out of user management.
        $mainAdminId = DB::table('users')->where('email', 'admin@gmail.com')->value('id')
            ?: DB::table('users')->orderBy('id')->value('id');

        if ($mainAdminId) {
            DB::table('users')->where('id', $mainAdminId)->update(['is_super_admin' => true]);
        }

        // Any other account that already existed keeps full module access, so this
        // migration never takes away something someone could do yesterday. Accounts
        // created from here on start with nothing ticked.
        DB::table('users')
            ->where('is_super_admin', false)
            ->update(['modules' => json_encode(AdminModules::keys())]);
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['is_super_admin', 'modules']);
        });
    }
};
