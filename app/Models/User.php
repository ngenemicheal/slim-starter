<?php

/**
 * User model.
 *
 * Backed by the `users` table (see database/migrations/001_create_users_table.sql).
 *
 * How to add a new model:
 *   1. Create app/Models/Post.php extending Illuminate\Database\Eloquent\Model
 *   2. Add the matching migration SQL to database/migrations/
 *   3. Use it anywhere: Post::find(1), Post::where('user_id', $id)->get(), etc.
 */

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class User extends Model
{
    /**
     * Columns that can be mass-assigned (e.g. User::create([...])).
     * Never put 'password' in fillable — always hash before assigning.
     */
    protected $fillable = [
        'name',
        'email',
        'password',
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
    ];

    /**
     * Verify a plain-text password against the stored bcrypt hash.
     */
    public function verifyPassword(string $plaintext): bool
    {
        return password_verify($plaintext, $this->password);
    }

    // ── Relationships (add yours below) ────────────────────────────────────
    // public function posts(): \Illuminate\Database\Eloquent\Relations\HasMany
    // {
    //     return $this->hasMany(Post::class);
    // }
}
