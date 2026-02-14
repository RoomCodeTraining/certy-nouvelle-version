<?php

namespace App\Services;

use App\Models\Document;
use PhpOffice\PhpWord\Element\TextBreak;
use PhpOffice\PhpWord\IOFactory;
use Smalot\PdfParser\Parser;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Spatie\PdfToImage\Pdf as PdfToImage;
use thiagoalessio\TesseractOCR\TesseractOCR;

class DocumentTextExtractor
{
    private const MAX_CHARS = 15000;

    private const MIN_TEXT_FOR_OCR_FALLBACK = 50;

    private const OCR_MAX_PAGES = 20;

    private const EXTRACTABLE_MIMES = [
        'application/pdf' => 'pdf',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => 'docx',
    ];

    public static function extractable(?string $mimeType): bool
    {
        return isset(self::EXTRACTABLE_MIMES[$mimeType ?? '']);
    }

    public static function extract(Document $document): ?string
    {
        if (! self::extractable($document->mime_type)) {
            return null;
        }

        $path = Storage::disk('local')->path($document->path);
        if (! is_readable($path)) {
            return null;
        }

        try {
            $text = match (self::EXTRACTABLE_MIMES[$document->mime_type] ?? null) {
                'pdf' => self::extractPdf($path),
                'docx' => self::extractDocx($path),
                default => null,
            };

            return $text ? mb_substr(trim($text), 0, self::MAX_CHARS) : null;
        } catch (\Throwable) {
            return null;
        }
    }

    private static function extractPdf(string $path): ?string
    {
        $parser = new Parser;
        $pdf = $parser->parseFile($path);
        $text = $pdf->getText() ?: null;

        if ($text !== null) {
            $trimmed = trim($text);
            if (mb_strlen($trimmed) >= self::MIN_TEXT_FOR_OCR_FALLBACK) {
                return $trimmed;
            }
        }

        return self::extractPdfWithOcr($path) ?? $text;
    }

    private static function extractPdfWithOcr(string $path): ?string
    {
        if (! config('services.ocr.enabled', true)) {
            return null;
        }

        $tempDir = storage_path('app/temp/ocr/' . uniqid('pdf_', true));
        if (! is_dir($tempDir) && ! mkdir($tempDir, 0755, true)) {
            return null;
        }

        try {
            $pdf = new PdfToImage($path);
            $pdf->resolution(200);

            $pageCount = $pdf->pageCount();
            if ($pageCount === 0) {
                return null;
            }

            $pagesToProcess = min($pageCount, self::OCR_MAX_PAGES);
            $pdf->selectPages(...range(1, $pagesToProcess));

            $imagePaths = $pdf->save($tempDir, 'page');

            $fullText = '';
            $language = config('services.ocr.language', 'fra+eng');

            foreach ($imagePaths as $imagePath) {
                $ocr = new TesseractOCR($imagePath);
                $ocr->lang($language);
                $pageText = $ocr->run();
                if ($pageText) {
                    $fullText .= $pageText . "\n\n";
                }
            }

            return trim($fullText) ?: null;
        } catch (\Throwable $e) {
            Log::warning('OCR PDF échoué', [
                'path' => $path,
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return null;
        } finally {
            if (is_dir($tempDir)) {
                array_map('unlink', glob($tempDir . '/*') ?: []);
                @rmdir($tempDir);
            }
        }
    }

    private static function extractDocx(string $path): ?string
    {
        $phpWord = IOFactory::load($path);
        $text = '';
        foreach ($phpWord->getSections() as $section) {
            foreach ($section->getElements() as $element) {
                $text .= self::getElementText($element);
            }
        }

        return trim($text) ?: null;
    }

    private static function getElementText(mixed $element): string
    {
        if ($element instanceof TextBreak) {
            return "\n";
        }
        if (method_exists($element, 'getText')) {
            return $element->getText();
        }
        if (method_exists($element, 'getElements')) {
            $text = '';
            foreach ($element->getElements() as $child) {
                $text .= self::getElementText($child);
            }
            return $text;
        }
        return '';
    }
}
