<?php

namespace App\Support;

use App\Contracts\LlmClient;

class FakeLlmClient implements LlmClient
{
    public static function complete(string $system, string $prompt): string
    {
        return 'fake response';
    }
}
