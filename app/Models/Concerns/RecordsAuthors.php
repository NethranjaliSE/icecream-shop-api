<?php

namespace App\Models\Concerns;

use App\Models\User;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Auth;

/**
 * Fills created_by / updated_by with the authenticated user, so every
 * inventory and customer change can be traced back to a staff member.
 */
trait RecordsAuthors
{
    public static function bootRecordsAuthors(): void
    {
        static::creating(function ($model) {
            $userId = Auth::id();

            if ($userId !== null) {
                $model->created_by ??= $userId;
                $model->updated_by ??= $userId;
            }
        });

        static::updating(function ($model) {
            if (($userId = Auth::id()) !== null) {
                $model->updated_by = $userId;
            }
        });
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
