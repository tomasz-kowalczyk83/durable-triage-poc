<?php

use App\Mcp\Servers\TriageServer;
use Laravel\Mcp\Facades\Mcp;

Mcp::local('triage', TriageServer::class);

Mcp::web('/mcp/triage', TriageServer::class)
    ->middleware(['throttle:60,1']);
