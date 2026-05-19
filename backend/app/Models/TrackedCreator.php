<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\TrackedCreatorFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TrackedCreator extends Model
{
    /** @use HasFactory<TrackedCreatorFactory> */
    use HasFactory;

    protected $fillable = [
        'user_id',
        'creator_profile_id',
        'network',
        'handle',
        'label',
        'refresh_cadence_minutes',
        'added_at',
    ];

    protected $casts = [
        'added_at' => 'datetime',
        'refresh_cadence_minutes' => 'int',
    ];

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo<CreatorProfile, $this>
     */
    public function creatorProfile(): BelongsTo
    {
        return $this->belongsTo(CreatorProfile::class);
    }
}
