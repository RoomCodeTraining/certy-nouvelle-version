<?php

use App\Mcp\Servers\ArchivesServer;
use Laravel\Mcp\Facades\Mcp;

Mcp::web('/mcp/archives', ArchivesServer::class)->middleware(['throttle:60']);
Mcp::local('archives', ArchivesServer::class);
