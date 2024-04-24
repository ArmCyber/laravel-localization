<?php

namespace ArmCyber\Localization\Commands;

use ArmCyber\Localization\Services\TranslationService;
use ArmCyber\Localization\Services\PoEditorApiService;
use Illuminate\Http\Client\RequestException;

class ExportTranslationsCommand extends BaseTranslationsCommand
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'ts:export';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Export translations and upload to PoEditor.';

    /**
     * ExportImportService instance, to be injected.
     *
     * @var TranslationService
     */
    protected TranslationService $translationService;

    /**
     * PoEditorApiService instance, to be injected.
     *
     * @var PoEditorApiService
     */
    protected PoEditorApiService $poEditorApiService;

    /**
     * Run view:cache.
     *
     * @return void
     */
    private function compileViews(): void
    {
        $this->warn('Compiling views.');
        $this->translationService->runCommand('view:cache', true);
    }

    /**
     * Update terms by the code.
     *
     * @return void
     */

    private function updateTerms(): void
    {
        $this->warn('Scanning files.');
        $this->translationService->createFileList();
        $this->warn('Extracting terms from scanned files.');
        $this->translationService->extractTerms();
    }

    /**
     * Upload terms to POEditor.
     *
     * @return void
     * @throws RequestException
     */
    private function uploadTerms(): void
    {
        $this->warn('Uploading terms to POEditor.');
        $file = $this->translationService->getTempTermsFile();
        $deleteRemovedTerms = config('localization.poeditor.poeditor_delete_removed_terms', false);
        $result = $this->poEditorApiService->uploadTerms($file, $deleteRemovedTerms);
        $resultString = "Parsed: {$result['parsed']}, added: {$result['added']}";
        if ($deleteRemovedTerms) {
            $resultString .= ", deleted: {$result['deleted']}";
        }
        $this->info("Terms uploaded successfully. {$resultString}.");
    }

    /**
     * @return void
     * @throws RequestException
     */
    protected function processCommand(): void
    {
        $this->compileViews();
        $this->updateTerms();
        $this->uploadTerms();
    }
}
