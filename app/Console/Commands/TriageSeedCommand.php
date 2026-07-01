<?php

namespace App\Console\Commands;

use Database\Seeders\IncidentSeeder;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('triage:seed')]
#[Description('Seed a fake incident for triage demos')]
class TriageSeedCommand extends Command
{
    public function handle(): int
    {
        $this->call(IncidentSeeder::class);

        $this->info('Seeded demo incident. Use `php artisan triage:durable {id}` to start a run.');

        return self::SUCCESS;
    }
}
