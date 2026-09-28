<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One right given to, or taken from, one person, whatever their position says.
 *
 * @see User::hasPermissionTo()
 */
class PermissionOverride extends Model
{
    protected $fillable = ['user_id', 'permission', 'allowed'];

    protected function casts(): array
    {
        return ['allowed' => 'boolean'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
