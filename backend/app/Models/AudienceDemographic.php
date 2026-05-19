<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AudienceDemographic extends Model
{
    protected $fillable = [
        'oauth_account_id',
        'captured_at',
        'dimension',
        'bucket',
        'value_pct',
        'raw',
    ];

    protected $casts = [
        'captured_at' => 'datetime',
        'raw' => 'array',
        'value_pct' => 'float',
    ];

    /**
     * @return BelongsTo<OauthAccount, $this>
     */
    public function oauthAccount(): BelongsTo
    {
        return $this->belongsTo(OauthAccount::class);
    }
}
