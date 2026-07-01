<?php

namespace App\Mcp\Tools;

use App\Models\Incident;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;

#[Description('List incidents in the triage system, optionally filtered by status.')]
class ListIncidentsTool extends Tool
{
    public function handle(Request $request): Response
    {
        $status = $request->has('status') ? $request->string('status')->toString() : null;
        $limit = min(max($request->integer('limit', 20), 1), 100);

        $incidents = Incident::query()
            ->when(filled($status), fn ($query) => $query->where('status', $status))
            ->latest('id')
            ->limit($limit)
            ->get(['id', 'status', 'severity', 'raw_payload', 'created_at']);

        return Response::json([
            'incidents' => $incidents->map(fn (Incident $incident): array => [
                'id' => $incident->id,
                'status' => $incident->status,
                'severity' => $incident->severity,
                'payload_excerpt' => $incident->payload_excerpt,
                'created_at' => $incident->created_at?->toIso8601String(),
            ])->all(),
        ]);
    }

    /**
     * @return array<string, Type>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'status' => $schema->string()
                ->description('Optional incident status filter.')
                ->nullable(),
            'limit' => $schema->integer()
                ->description('Maximum number of incidents to return (1-100).')
                ->default(20),
        ];
    }
}
