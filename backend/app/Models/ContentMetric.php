<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ContentMetric extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'content_item_id',
        'captured_at',
        'views',
        'likes',
        'comments',
        'shares',
        'saves',
        'reach',
        'impressions',
        'watch_time_s',
    ];

    protected $casts = [
        'captured_at' => 'datetime',
    ];

    /**
     * @return BelongsTo<ContentItem, $this>
     */
    public function contentItem(): BelongsTo
    {
        return $this->belongsTo(ContentItem::class);
    }
}
