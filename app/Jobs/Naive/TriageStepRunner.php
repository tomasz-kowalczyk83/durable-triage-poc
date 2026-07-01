<?php

namespace App\Jobs\Naive;

use App\Contracts\LlmClient;
use App\Enums\Severity;
use App\Models\Incident;
use App\Support\FakeLlmClient;
use App\Workflows\Data\ClassifyResult;
use App\Workflows\Data\CorrelateResult;
use App\Workflows\Data\CorrelationRef;
use App\Workflows\Data\RunbookRef;
use App\Workflows\Data\RunbookResult;
use App\Workflows\Data\SynthesiseResult;

class TriageStepRunner
{
    public function clientClass(): string
    {
        return config('triage.llm_client', FakeLlmClient::class);
    }

    public function complete(string $system, string $prompt): string
    {
        $client = $this->clientClass();

        /** @var class-string<LlmClient> $client */
        return $client::complete($system, $prompt);
    }

    public function classify(Incident $incident): ClassifyResult
    {
        $raw = $this->complete('classify', $incident->raw_payload);
        $data = json_decode($raw, true);

        if (is_array($data) && isset($data['severity'])) {
            return new ClassifyResult(
                Severity::from($data['severity']),
                (float) ($data['confidence'] ?? 1.0),
            );
        }

        return new ClassifyResult(Severity::Sev2, 0.5);
    }

    public function correlate(Incident $incident): CorrelateResult
    {
        $raw = $this->complete('correlate', $incident->raw_payload);
        $data = json_decode($raw, true);

        if (! is_array($data) || ! isset($data['correlationIds'])) {
            return new CorrelateResult([]);
        }

        $refs = array_map(
            fn (array $row) => new CorrelationRef(
                (int) ($row['incidentId'] ?? 0),
                (string) ($row['title'] ?? ''),
                (float) ($row['similarity'] ?? 0.0),
            ),
            $data['correlationIds'],
        );

        return new CorrelateResult($refs);
    }

    public function runbook(Incident $incident): RunbookResult
    {
        $raw = $this->complete('runbook', $incident->raw_payload);
        $data = json_decode($raw, true);

        if (! is_array($data) || ! isset($data['runbooks'])) {
            return new RunbookResult([]);
        }

        $refs = array_map(
            fn (array $row) => new RunbookRef(
                (string) ($row['slug'] ?? ''),
                (string) ($row['title'] ?? ''),
                isset($row['url']) ? (string) $row['url'] : null,
            ),
            $data['runbooks'],
        );

        return new RunbookResult($refs);
    }

    public function synthesise(Incident $incident): SynthesiseResult
    {
        $severity = Severity::from($incident->severity ?? Severity::Sev2->value);
        $correlations = array_map(
            fn (array $row) => CorrelationRef::from($row),
            $incident->correlation_ids ?? [],
        );
        $runbooks = array_map(
            fn (array $row) => RunbookRef::from($row),
            $incident->runbook_refs ?? [],
        );

        $prompt = json_encode([
            'incidentId' => $incident->id,
            'severity' => $severity->value,
            'correlations' => $incident->correlation_ids ?? [],
            'runbooks' => $incident->runbook_refs ?? [],
        ]);

        $raw = $this->complete('synthesise', $prompt);
        $data = json_decode($raw, true);

        if (is_array($data) && isset($data['suggestion'])) {
            return new SynthesiseResult((string) $data['suggestion']);
        }

        return new SynthesiseResult($raw);
    }

    public function act(Incident $incident): string
    {
        $prompt = json_encode([
            'incidentId' => $incident->id,
            'suggestion' => $incident->suggestion,
        ]);

        $raw = $this->complete('act', $prompt);
        $data = json_decode($raw, true);

        if (is_array($data) && isset($data['actionRef'])) {
            return (string) $data['actionRef'];
        }

        return 'action-'.$incident->id;
    }

    public function escalate(Incident $incident): void
    {
        $this->complete('escalate', json_encode(['incidentId' => $incident->id]));
    }
}
