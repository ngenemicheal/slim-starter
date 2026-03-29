<?php

/**
 * User model.
 *
 * Backed by the `users` table — see:
 *   database/migrations/001_create_users_table.sql
 *   database/migrations/002_add_role_status_to_users.sql
 */

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class User extends Model
{
    // ── Role and status constants ──────────────────────────────────────────
    public const ROLE_USER  = 'user';
    public const ROLE_ADMIN = 'admin';

    public const STATUS_ACTIVE   = 'active';
    public const STATUS_INACTIVE = 'inactive';

    /**
     * Columns available for mass-assignment.
     * Password must always be pre-hashed before calling create() or update().
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'status',
    ];

    /**
     * Columns hidden from toArray() / toJson() (e.g. API responses).
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Automatic type casting.
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'created_at'        => 'datetime',
        'updated_at'        => 'datetime',
    ];

    // ── Helpers ────────────────────────────────────────────────────────────

    public function verifyPassword(string $plaintext): bool
    {
        return password_verify($plaintext, $this->password);
    }

    public function isAdmin(): bool
    {
        return $this->role === self::ROLE_ADMIN;
    }

    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }

    // ── Relationships (add yours below) ────────────────────────────────────
    // public function posts(): \Illuminate\Database\Eloquent\Relations\HasMany
    // {
    //     return $this->hasMany(Post::class);
    // }
}
