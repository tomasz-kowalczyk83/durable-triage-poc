<?php

namespace App\Support;

use App\Contracts\LlmClient;
use RuntimeException;

class FakeLlmClient implements LlmClient
{
    /** @var array<string, int> */
    private static array $callCounts = [];

    private static int $totalCalls = 0;

    private static ?int $failAt = null;

    /** @var array<string, string> */
    private static array $responses = [
        'classify' => '{"severity":"SEV2","confidence":0.92}',
        'correlate' => '{"correlationIds":[{"incidentId":42,"title":"same service","similarity":0.85}]}',
        'runbook' => '{"runbooks":[{"slug":"rb-001","title":"Restart pod"}]}',
        'synthesise' => '{"suggestion":"Scale up replicas and restart the affected pod."}',
        'act' => '{"actionRef":"act-001"}',
    ];

    public static function complete(string $system, string $prompt): string
    {
        $key = self::resolveKey($system);

        self::$totalCalls++;

        if (self::$failAt !== null && self::$totalCalls === self::$failAt) {
            throw new RuntimeException(
                'FakeLlmClient forced failure at step '.self::$failAt." (key: {$key}, call #".self::$totalCalls.')'
            );
        }

        self::recordCall($key);

        return self::$responses[$key] ?? '{"result":"ok"}';
    }

    public static function getCallCount(string $key): int
    {
        return self::$callCounts[$key] ?? 0;
    }

    public static function resetCounts(): void
    {
        self::$callCounts = [];
        self::$totalCalls = 0;
    }

    public static function recordCall(string $key): void
    {
        self::$callCounts[$key] = (self::$callCounts[$key] ?? 0) + 1;
    }

    public static function setFailAt(?int $step): void
    {
        self::$failAt = $step;
    }

    public static function getTotalCalls(): int
    {
        return self::$totalCalls;
    }

    private static function resolveKey(string $system): string
    {
        $normalized = strtolower(trim($system));

        foreach (array_keys(self::$responses) as $key) {
            if ($normalized === $key || str_contains($normalized, $key)) {
                return $key;
            }
        }

        return $normalized !== '' ? $normalized : 'unknown';
    }
}
