<?php

namespace App\Mcp\Servers;

use App\Mcp\Tools\GetIncidentTool;
use App\Mcp\Tools\ListIncidentsTool;
use Laravel\Mcp\Server;
use Laravel\Mcp\Server\Attributes\Instructions;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Attributes\Version;
use Laravel\Mcp\Server\Prompt;
use Laravel\Mcp\Server\Tool;

#[Name('Triage Server')]
#[Version('0.1.0')]
#[Instructions('Expose incident triage data from this application. Use list-incidents to browse incidents and get-incident for full details.')]
class TriageServer extends Server
{
    /**
     * @var array<int, class-string<Tool>>
     */
    protected array $tools = [
        ListIncidentsTool::class,
        GetIncidentTool::class,
    ];

    /**
     * @var array<int, class-string<Server\Resource>>
     */
    protected array $resources = [];

    /**
     * @var array<int, class-string<Prompt>>
     */
    protected array $prompts = [];
}
