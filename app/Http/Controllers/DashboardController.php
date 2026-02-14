<?php

namespace App\Http\Controllers;

use App\Models\AssistantMessage;
use App\Models\Document;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __invoke(): Response
    {
        $user = auth()->user();
        $organization = $user->currentOrganization();

        $stats = [
            'documentCount' => 0,
            'totalSize' => 0,
            'documentsPerDay' => [],
            'documentsByType' => [],
            'assistantExchangesCount' => 0,
        ];

        if ($organization) {
            $docs = Document::where('organization_id', $organization->id);

            $stats['documentCount'] = (clone $docs)->count();
            $stats['totalSize'] = (clone $docs)->sum('size');

            $perDay = (clone $docs)
                ->where('created_at', '>=', Carbon::today()->subDays(6))
                ->selectRaw('DATE(created_at) as date, COUNT(*) as count')
                ->groupBy('date')
                ->pluck('count', 'date')
                ->all();
            $stats['documentsPerDay'] = collect(range(6, 0))
                ->map(fn ($i) => Carbon::today()->subDays($i)->format('Y-m-d'))
                ->map(fn ($d) => [
                    'date' => $d,
                    'label' => Carbon::parse($d)->format('j') . ' ' . Carbon::parse($d)->translatedFormat('M'),
                    'count' => (int) ($perDay[$d] ?? 0),
                ])
                ->values()
                ->all();

            $total = $stats['documentCount'] ?: 1;
            $byType = (clone $docs)
                ->select('mime_type', DB::raw('COUNT(*) as count'))
                ->groupBy('mime_type')
                ->get();
            $stats['documentsByType'] = $byType
                ->map(fn ($r) => [
                    'label' => $this->mimeLabel($r->mime_type),
                    'count' => (int) $r->count,
                    'percent' => round((int) $r->count / $total * 100),
                ])
                ->sortByDesc('count')
                ->values()
                ->take(5)
                ->all();

            $stats['assistantExchangesCount'] = AssistantMessage::where('organization_id', $organization->id)
                ->where('user_id', $user->id)
                ->where('role', 'assistant')
                ->count();
        }

        $recentDocuments = [];
        if ($organization) {
            $recentDocuments = Document::where('organization_id', $organization->id)
                ->orderByDesc('created_at')
                ->take(5)
                ->get()
                ->map(fn ($d) => [
                    'id' => $d->id,
                    'title' => $d->title ?: $d->filename,
                    'date' => $d->created_at->translatedFormat('d M, H:i'),
                    'type' => $this->mimeLabel($d->mime_type),
                ])
                ->all();
        }

        return Inertia::render('Dashboard', [
            'organization' => $organization,
            'stats' => $stats,
            'recentDocuments' => $recentDocuments,
        ]);
    }

    private function mimeLabel(?string $mime): string
    {
        return match (true) {
            str_starts_with($mime ?? '', 'application/pdf') => 'PDF',
            str_contains($mime ?? '', 'word') || str_contains($mime ?? '', 'document') => 'Word',
            str_contains($mime ?? '', 'sheet') || str_contains($mime ?? '', 'excel') => 'Excel',
            str_contains($mime ?? '', 'image') => 'Image',
            str_contains($mime ?? '', 'text') => 'Texte',
            default => 'Autre',
        };
    }
}
