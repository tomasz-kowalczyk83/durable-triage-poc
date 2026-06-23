<?php

namespace App\Contracts;

interface LlmClient
{
    public static function complete(string $system, string $prompt): string;
}
