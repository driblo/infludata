<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CreatorProfile extends Model
{
    protected $fillable = [
        'network',
        'platform_user_id',
        'handle',
        'display_name',
        'avatar_url',
        'bio',
        'follower_count',
        'following_count',
        'is_verified',
        'country',
        'raw_payload',
        'fetched_at',
    ];

    protected $casts = [
        'raw_payload' => 'array',
        'is_verified' => 'bool',
        'follower_count' => 'int',
        'following_count' => 'int',
        'fetched_at' => 'datetime',
    ];

    /**
     * @return HasMany<MetricSnapshot, $this>
     */
    public function metricSnapshots(): HasMany
    {
        return $this->hasMany(MetricSnapshot::class);
    }

    /**
     * @return HasMany<ContentItem, $this>
     */
    public function contentItems(): HasMany
    {
        return $this->hasMany(ContentItem::class);
    }
}
