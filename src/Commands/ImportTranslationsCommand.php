<?php

namespace ArmCyber\Localization\Commands;

use ArmCyber\Localization\Services\TranslationService;
use ArmCyber\Localization\Services\PoEditorApiService;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\File;

class ImportTranslationsCommand extends BaseTranslationsCommand
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'ts:import';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Download translations from PoEditor and import.';

    /**
     * ExportImportService instance, to be injected.
     *
     * @var TranslationService
     */
    protected TranslationService $translationService;

    /**
     * Locales configuration, to be preloaded.
     *
     * @var array
     */

    private array $locales;
    /**
     * File mime types configuration, to be preloaded.
     *
     * @var array
     */
    private array $types;

    /**
     * PoEditorApiService instance, to be injected.
     *
     * @var PoEditorApiService
     */
    protected PoEditorApiService $poEditorApiService;

    /**
     * Get file name to store locally.
     *
     * @param $locale
     * @param $type
     * @return string
     */
    private function getImportedFilename($locale, $type): string
    {
        return $locale . '.' . $type;
    }

    /**
     * Import translations and store locally.
     *
     * @return void
     * @throws RequestException
     */
    private function importTranslations(): void
    {
        $exportsPath = $this->translationService->getTempExportsPath();
        if (!File::exists($exportsPath)) {
            File::makeDirectory($exportsPath);
        }
        foreach ($this->locales as $poEditorLocale => $locale) {
            foreach ($this->types as $type => $filename) {
                $this->warn("Importing translations of locale $locale as .{$type}.");
                $destination = $exportsPath . $this->getImportedFilename($locale, $type);
                $this->poEditorApiService->downloadTranslation($poEditorLocale, $type, $destination);
            }
        }
    }

    /**
     * Store translations.
     *
     * @return void
     */
    private function storeTranslations(): void
    {
        $this->info("Translations imported successfully.");

        $this->translationService->clearNfsCacheForLocalesPath();

        foreach ($this->locales as $locale) {
            foreach ($this->types as $type => $filename) {
                $importedFile = $this->getImportedFilename($locale, $type);
                $this->translationService->storeTranslation($importedFile, $locale, $filename);
            }
        }
    }

    /**
     * @throws RequestException
     */
    protected function processCommand()
    {
        $this->warn('Starting import process.');
        $this->locales = config('localization.locales', []);
        $this->types = config('localization.import_file_types', []);

        // Store locally first, so in case of failure of one translation, process will be canceled.
        $this->importTranslations();

        // At this point, we know that all translations of all types imported successfully.
        $this->storeTranslations();
    }
}
