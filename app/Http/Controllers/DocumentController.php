<?php

namespace App\Http\Controllers;

use App\Models\Document;
use App\Services\SubscriptionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DocumentController extends Controller
{
    public function index(Request $request): Response
    {
        $organization = $request->user()->currentOrganization();

        $documents = $organization
            ? $organization->documents()
                ->with('uploader:id,name')
                ->latest()
                ->get()
            : collect();

        return Inertia::render('Documents/Index', [
            'documents' => $documents,
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'file' => 'required|file|max:10240', // 10MB
        ]);

        $organization = $request->user()->currentOrganization();
        if (! $organization) {
            return back()->withErrors(['file' => 'Aucune organisation associée.']);
        }

        $subscriptionService = app(SubscriptionService::class);
        if (! $subscriptionService->canUploadDocument($organization)) {
            return back()->with('error', 'Quota de documents atteint. Passez à un plan supérieur.');
        }

        Document::storeUpload(
            $organization,
            $request->user(),
            $request->file('file')
        );

        return redirect()->back()->with('success', 'Document enregistré.');
    }

    public function download(Request $request, Document $document): StreamedResponse
    {
        $organization = $request->user()->currentOrganization();
        if (! $organization || $document->organization_id !== $organization->id) {
            abort(403);
        }

        if (! Storage::disk('local')->exists($document->path)) {
            abort(404);
        }

        return Storage::disk('local')->download(
            $document->path,
            $document->filename,
            [
                'Content-Type' => $document->mime_type ?? 'application/octet-stream',
            ]
        );
    }

    public function destroy(Request $request, Document $document)
    {
        $organization = $request->user()->currentOrganization();
        if (! $organization || $document->organization_id !== $organization->id) {
            abort(403);
        }

        Storage::disk('local')->delete($document->path);
        $document->delete();

        return redirect()->back()->with('success', 'Document supprimé.');
    }
}