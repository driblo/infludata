<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ApiJob extends Model
{
    protected $fillable = [
        'job_class',
        'payload',
        'idempotency_key',
        'status',
        'started_at',
        'finished_at',
        'attempts',
        'error',
        'oauth_account_id',
        'tracked_creator_id',
    ];

    protected $casts = [
        'payload' => 'array',
        'started_at' => 'datetime',
        'finished_at' => 'datetime',
        'attempts' => 'int',
    ];
}
