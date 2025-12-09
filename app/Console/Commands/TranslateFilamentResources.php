<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class TranslateFilamentResources extends Command
{
    protected $signature = 'filament:translate-resources';
    protected $description = 'Auto-translate Filament resources labels to Arabic';

    public function handle()
    {
        $path = app_path('Filament');
        $files = $this->getPhpFiles($path);

        foreach ($files as $file) {
            $this->translateFile($file);
        }

        $this->info("✔ All Filament resources scanned and translated.");
    }

    private function getPhpFiles($dir)
    {
        $rii = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($dir));
        $files = [];

        foreach ($rii as $file) {
            if (!$file->isDir() && $file->getExtension() === 'php') {
                $files[] = $file->getPathname();
            }
        }

        return $files;
    }

    private function translateFile($file)
    {
        $content = file_get_contents($file);
        $originalContent = $content;

        // Patterns to translate
        $patterns = [
            '/->label\([\'"](.*?)[\'"]\)/',
            '/->title\([\'"](.*?)[\'"]\)/',
            '/Section::make\([\'"](.*?)[\'"]\)/',
            '/static\s*\?\$modelLabel\s*=\s*[\'"](.*?)[\'"]/',
            '/static\s*\?\$pluralModelLabel\s*=\s*[\'"](.*?)[\'"]/',
            '/static function getLabel\(\).*?return [\'"](.*?)[\'"];/s',
            '/static function getPluralLabel\(\).*?return [\'"](.*?)[\'"];/s',
            '/static\s*\?\$navigationLabel\s*=\s*[\'"](.*?)[\'"]/',
        ];

        foreach ($patterns as $pattern) {
            preg_match_all($pattern, $content, $matches);

            foreach ($matches[1] as $englishText) {
                $arabic = $this->translate($englishText);

                if ($arabic && $arabic !== $englishText) {
                    $content = str_replace($englishText, $arabic, $content);
                }
            }
        }

        if ($content !== $originalContent) {
            file_put_contents($file, $content);
            $this->info("Translated: " . basename($file));
        } else {
            $this->info("No labels found in: " . basename($file));
        }
    }

    private function translate($text)
    {
        // Prevent re-translating Arabic
        if (preg_match('/\p{Arabic}/u', $text)) {
            return $text;
        }

        try {
            $response = Http::timeout(10)->post('https://libretranslate.com/translate', [
                'q' => $text,
                'source' => 'en',
                'target' => 'ar',
            ]);

            return $response->json()['translatedText'] ?? $text;

        } catch (\Exception $e) {
            return $text;
        }
    }
}
