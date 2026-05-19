<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\OauthAccountFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OauthAccount extends Model
{
    /** @use HasFactory<OauthAccountFactory> */
    use HasFactory;

    protected $fillable = [
        'user_id',
        'network',
        'phyllo_account_id',
        'phyllo_user_id',
        'external_handle',
        'scopes',
        'status',
        'connected_at',
        'last_synced_at',
    ];

    protected $casts = [
        'scopes' => 'array',
        'connected_at' => 'datetime',
        'last_synced_at' => 'datetime',
    ];

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
