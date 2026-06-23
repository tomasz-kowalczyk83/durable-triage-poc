<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Incident extends Model
{
    protected $fillable = [
        'raw_payload',
        'severity',
        'correlation_ids',
        'runbook_refs',
        'suggestion',
        'status',
    ];

    protected $casts = [
        'correlation_ids' => 'array',
        'runbook_refs' => 'array',
    ];
}
