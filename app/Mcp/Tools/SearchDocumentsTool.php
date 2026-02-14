<?php

namespace App\Mcp\Tools;

use App\Models\Document;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;

class SearchDocumentsTool extends Tool
{
    protected string $description = 'Search documents by title or filename within an organization. Returns matching documents.';

    public function handle(Request $request): Response
    {
        $query = $request->string('query');
        $orgSlug = $request->string('organization_slug');

        $org = \App\Models\Organization::where('slug', $orgSlug)->first();

        if (! $org) {
            return Response::error("Organization not found: {$orgSlug}");
        }

        $documents = $org->documents()
            ->where(function ($q) use ($query) {
                $q->where('title', 'like', "%{$query}%")
                    ->orWhere('filename', 'like', "%{$query}%");
            })
            ->latest()
            ->get(['id', 'title', 'filename', 'size', 'created_at']);

        $content = $documents->isEmpty()
            ? "No documents matching '{$query}' in {$org->name}."
            : "Found " . $documents->count() . " document(s):\n\n" . $documents->map(fn ($d) => "- {$d->title} ({$d->filename})")->join("\n");

        return Response::text($content);
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'query' => $schema->string()->description('Search term for title or filename')->required(),
            'organization_slug' => $schema->string()->description('Organization slug (e.g. from documents resource URI)')->required(),
        ];
    }
}
