<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WebhookEvent extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'provider',
        'event_type',
        'payload',
        'signature_ok',
        'received_at',
        'processed_at',
        'status',
        'attempts',
        'error',
    ];

    protected $casts = [
        'payload' => 'array',
        'signature_ok' => 'bool',
        'received_at' => 'datetime',
        'processed_at' => 'datetime',
        'attempts' => 'int',
    ];
}
