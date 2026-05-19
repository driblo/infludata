<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ContentItem extends Model
{
    protected $fillable = [
        'creator_profile_id',
        'network',
        'external_id',
        'kind',
        'title',
        'caption',
        'url',
        'thumbnail_url',
        'duration_s',
        'published_at',
        'raw',
    ];

    protected $casts = [
        'published_at' => 'datetime',
        'raw' => 'array',
        'duration_s' => 'int',
    ];

    /**
     * @return BelongsTo<CreatorProfile, $this>
     */
    public function creatorProfile(): BelongsTo
    {
        return $this->belongsTo(CreatorProfile::class);
    }

    /**
     * @return HasMany<ContentMetric, $this>
     */
    public function metrics(): HasMany
    {
        return $this->hasMany(ContentMetric::class);
    }
}
