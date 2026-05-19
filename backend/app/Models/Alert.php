<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\AlertFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Alert extends Model
{
    /** @use HasFactory<AlertFactory> */
    use HasFactory;

    protected $fillable = [
        'user_id',
        'target_type',
        'target_id',
        'kind',
        'threshold',
        'channel',
        'enabled',
        'last_fired_at',
    ];

    protected $casts = [
        'threshold' => 'array',
        'enabled' => 'bool',
        'last_fired_at' => 'datetime',
    ];

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
