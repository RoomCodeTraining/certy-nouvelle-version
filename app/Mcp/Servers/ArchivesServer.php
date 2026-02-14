<?php

namespace App\Mcp\Servers;

use App\Mcp\Resources\DocumentsResource;
use App\Mcp\Tools\SearchDocumentsTool;
use Laravel\Mcp\Server;

class ArchivesServer extends Server
{
    protected string $name = 'archives';

    protected string $version = '0.0.1';

    protected string $instructions = <<<'MARKDOWN'
        This server exposes document archives from the Archives application.
        Use the documents resource to list available documents.
        Use the search_documents tool to find documents by query.
        Documents are organized by organization. Each organization has its own document store.
    MARKDOWN;

    protected array $tools = [
        SearchDocumentsTool::class,
    ];

    protected array $resources = [
        DocumentsResource::class,
    ];

    protected array $prompts = [];
}
