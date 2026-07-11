<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Stichoza\GoogleTranslate\GoogleTranslate;

class AutoTranslateJson extends Command
{
    protected $signature = 'translate:json {locale : Target locale code (e.g. id, en, ja)}';

    protected $description = 'Auto-translate untranslated entries in a lang JSON file using Google Translate';

    public function handle(): int
    {
        $locale = $this->argument('locale');
        $path = lang_path("{$locale}.json");

        if (! File::exists($path)) {
            $this->error("File lang/{$locale}.json not found.");
            $this->info("Run 'php artisan translatable:export {$locale}' first.");

            return 1;
        }

        $this->info("Reading lang/{$locale}.json ...");

        $translations = json_decode(File::get($path), true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            $this->error("Invalid JSON in lang/{$locale}.json.");

            return 1;
        }

        $totalStrings = count($translations);

        if ($totalStrings === 0) {
            $this->info('No strings found.');

            return 0;
        }

        $this->info("Translating to: {$locale}");

        $bar = $this->output->createProgressBar($totalStrings);
        $bar->start();

        // Assumes the source strings in Blade files are in English
        $tr = new GoogleTranslate();
        $tr->setSource('en');
        $tr->setTarget($locale);

        $updatedTranslations = [];
        $translatedCount = 0;

        foreach ($translations as $key => $value) {
            // Only translate entries where value still equals the key (not yet translated) or is empty
            if ($key === $value || empty(trim((string) $value))) {
                try {
                    // Brief pause to avoid Google rate limiting
                    usleep(200000);

                    $updatedTranslations[$key] = $tr->translate($key);
                    $translatedCount++;
                } catch (\Exception) {
                    // Preserve key as fallback if translation fails
                    $updatedTranslations[$key] = $key;
                }
            } else {
                // Preserve existing manual translations
                $updatedTranslations[$key] = $value;
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);

        File::put($path, json_encode($updatedTranslations, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

        $this->info("Done! {$translatedCount} strings translated and saved to lang/{$locale}.json.");

        return 0;
    }
}
