<?php

namespace App\Console\Commands;

use App\Models\Document;
use App\Services\DocumentTextExtractor;
use Illuminate\Console\Command;

class ExtractDocumentTextCommand extends Command
{
    protected $signature = 'documents:extract-text {--id= : Document ID} {--path= : Document path (e.g. documents/2/xxx.pdf)} {--debug : Affiche les détails pour chaque document}';

    protected $description = 'Extrait le texte des documents PDF/DOCX existants';

    public function handle(): int
    {
        $query = Document::query();

        if ($id = $this->option('id')) {
            $query->where('id', $id);
        }
        if ($path = $this->option('path')) {
            $query->where('path', 'like', '%' . basename($path) . '%');
        }

        $documents = $query->get();

        $extractable = $documents->filter(
            fn ($d) => DocumentTextExtractor::extractable($d->mime_type)
        );

        if ($extractable->isEmpty()) {
            $this->warn('Aucun document PDF/DOCX trouvé.');
            return Command::SUCCESS;
        }

        $this->info("Documents éligibles (PDF/DOCX) : {$extractable->count()}");
        $debug = $this->option('debug');

        foreach ($extractable as $doc) {
            try {
                $text = DocumentTextExtractor::extract($doc);
                $doc->update(['extracted_text' => $text]);
                $preview = $text ? mb_substr($text, 0, 80) . '...' : '(vide)';
                if ($debug) {
                    $this->line("  [{$doc->id}] {$doc->filename} : " . ($text ? mb_strlen($text) . ' car.' : 'vide'));
                    if ($text) {
                        $this->line("    Extrait : {$preview}");
                    }
                }
            } catch (\Throwable $e) {
                $this->error("  [{$doc->id}] {$doc->filename} : " . $e->getMessage());
                if ($debug) {
                    $this->line('    ' . $e->getFile() . ':' . $e->getLine());
                    $this->line('    ' . $e->getTraceAsString());
                }
            }
        }

        $this->info('Extraction terminée.');

        return Command::SUCCESS;
    }
}
