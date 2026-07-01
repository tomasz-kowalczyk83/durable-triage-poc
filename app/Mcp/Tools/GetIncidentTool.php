<?php

namespace App\Mcp\Tools;

use App\Models\Incident;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;

#[Description('Fetch a single incident by ID, including triage metadata.')]
class GetIncidentTool extends Tool
{
    public function handle(Request $request): Response
    {
        $incident = Incident::query()->find($request->integer('id'));

        if ($incident === null) {
            return Response::text('Incident not found.');
        }

        return Response::json([
            'id' => $incident->id,
            'status' => $incident->status,
            'severity' => $incident->severity,
            'raw_payload' => $incident->raw_payload,
            'correlation_ids' => $incident->correlation_ids,
            'runbook_refs' => $incident->runbook_refs,
            'suggestion' => $incident->suggestion,
            'created_at' => $incident->created_at?->toIso8601String(),
            'updated_at' => $incident->updated_at?->toIso8601String(),
        ]);
    }

    /**
     * @return array<string, Type>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'id' => $schema->integer()
                ->description('The incident ID.')
                ->required(),
        ];
    }
}
