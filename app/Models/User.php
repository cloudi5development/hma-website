<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Support\AdminModules;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'is_active',
        'last_login_at',
        'modules',
    ];

    /**
     * 'is_super_admin' is deliberately NOT fillable. The main admin is set once
     * by migration; no form or mass-assign can promote an account to it, so the
     * panel always has exactly one owner.
     */

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'last_login_at' => 'datetime',
            'is_active' => 'boolean',
            'is_super_admin' => 'boolean',
            'modules' => 'array',
            'password' => 'hashed',
        ];
    }

    /** Accounts allowed to sign in to the admin panel. */
    public function scopeActive(\Illuminate\Database\Eloquent\Builder $q): \Illuminate\Database\Eloquent\Builder
    {
        return $q->where('is_active', true);
    }

    /**
     * Whether this account may open an admin module (see Support\AdminModules).
     *
     * The main admin holds everything. Everyone else holds exactly what was
     * ticked for them — and never a super-admin-only module, however their
     * `modules` column got populated.
     */
    public function canAccessModule(string $module): bool
    {
        if ($this->is_super_admin) {
            return true;
        }

        if (AdminModules::isSuperAdminOnly($module)) {
            return false;
        }

        return in_array($module, $this->modules ?? [], true);
    }

    /**
     * The grantable modules this account holds, in registry order. Intersected
     * with the registry so a module that was later renamed or removed does not
     * linger in the UI.
     */
    public function moduleKeys(): array
    {
        if ($this->is_super_admin) {
            return AdminModules::keys();
        }

        return array_values(array_intersect(AdminModules::keys(), $this->modules ?? []));
    }
}
