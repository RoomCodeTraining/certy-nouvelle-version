<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class OcrDiagnosticsCommand extends Command
{
    protected $signature = 'ocr:diagnostics';

    protected $description = 'Vérifie que Imagick, Ghostscript et Tesseract sont installés pour l\'OCR des PDF scannés';

    public function handle(): int
    {
        $this->info('Vérification des prérequis OCR...');
        $this->newLine();

        $ok = true;

        // Imagick
        if (extension_loaded('imagick')) {
            $this->info('  [OK] Extension PHP Imagick');
        } else {
            $this->error('  [MANQUANT] Extension PHP Imagick - requis pour convertir PDF → images');
            $ok = false;
        }

        // Ghostscript (Imagick en a besoin pour les PDF)
        $gs = trim((string) shell_exec('which gs 2>/dev/null'));
        if ($gs) {
            $this->info("  [OK] Ghostscript : {$gs}");
        } else {
            $this->error('  [MANQUANT] Ghostscript (gs) - requis pour lire les PDF avec Imagick');
            $ok = false;
        }

        // Tesseract
        $tess = trim((string) shell_exec('which tesseract 2>/dev/null'));
        if ($tess) {
            $version = trim((string) shell_exec('tesseract --version 2>/dev/null | head -1'));
            $this->info("  [OK] Tesseract : {$tess}");
            if ($version) {
                $this->line("       {$version}");
            }
        } else {
            $this->error('  [MANQUANT] Tesseract OCR - requis pour extraire le texte des images');
            $ok = false;
        }

        // Langues Tesseract
        if ($tess) {
            $langs = trim((string) shell_exec('tesseract --list-langs 2>/dev/null'));
            if (str_contains($langs, 'fra') && str_contains($langs, 'eng')) {
                $this->info('  [OK] Langues Tesseract : fra, eng');
            } else {
                $this->warn('  [ATTENTION] Langues fra/eng manquantes. Installez tesseract-ocr-fra et tesseract-ocr-eng.');
            }
        }

        $this->newLine();
        if ($ok) {
            $this->info('Tous les prérequis sont installés.');
        } else {
            $this->error('Certains prérequis manquent. Reconstruisez l\'image DDEV : ddev restart --no-cache');
        }

        return $ok ? Command::SUCCESS : Command::FAILURE;
    }
}
