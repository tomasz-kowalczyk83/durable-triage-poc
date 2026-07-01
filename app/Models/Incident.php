<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * @property int $id
 * @property string $raw_payload
 * @property string|null $severity
 * @property array<int, string>|null $correlation_ids
 * @property array<int, string>|null $runbook_refs
 * @property string|null $suggestion
 * @property string $status
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read string $payload_excerpt
 */
class Incident extends Model
{
    use HasFactory;

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

    protected function payloadExcerpt(): Attribute
    {
        return Attribute::get(
            fn (): string => Str::limit($this->raw_payload, 80),
        );
    }
}
