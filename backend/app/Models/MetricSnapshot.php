<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\MetricSnapshotFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MetricSnapshot extends Model
{
    /** @use HasFactory<MetricSnapshotFactory> */
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'creator_profile_id',
        'network',
        'captured_at',
        'followers',
        'following',
        'posts_count',
        'total_likes',
        'total_views',
        'engagement_rate',
        'source',
        'raw',
    ];

    protected $casts = [
        'captured_at' => 'datetime',
        'raw' => 'array',
        'followers' => 'int',
        'following' => 'int',
        'posts_count' => 'int',
        'total_likes' => 'int',
        'total_views' => 'int',
        'engagement_rate' => 'float',
    ];

    /**
     * @return BelongsTo<CreatorProfile, $this>
     */
    public function creatorProfile(): BelongsTo
    {
        return $this->belongsTo(CreatorProfile::class);
    }
}
