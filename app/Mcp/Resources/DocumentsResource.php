<?php

namespace App\Mcp\Resources;

use App\Models\Document;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Contracts\HasUriTemplate;
use Laravel\Mcp\Server\Resource;
use Laravel\Mcp\Support\UriTemplate;

class DocumentsResource extends Resource implements HasUriTemplate
{
    protected string $description = 'List of documents in an organization. Use the organization slug to fetch documents.';

    public function uriTemplate(): UriTemplate
    {
        return new UriTemplate('archives://organizations/{organizationSlug}/documents');
    }

    public function handle(Request $request): Response
    {
        $slug = $request->get('organizationSlug');

        $org = \App\Models\Organization::where('slug', $slug)->first();

        if (! $org) {
            return Response::text("Organization not found: {$slug}");
        }

        $documents = $org->documents()
            ->latest()
            ->get(['id', 'title', 'filename', 'mime_type', 'size', 'created_at']);

        $content = $documents->isEmpty()
            ? "No documents in organization {$org->name}."
            : "Documents in {$org->name}:\n\n" . $documents->map(fn ($d) => "- {$d->title} ({$d->filename}) - " . number_format($d->size) . " bytes")->join("\n");

        return Response::text($content);
    }
}
