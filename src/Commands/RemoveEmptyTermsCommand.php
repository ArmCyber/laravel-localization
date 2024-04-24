<?php

namespace ArmCyber\Localization\Commands;

use ArmCyber\Localization\Services\LocalizationService;
use Illuminate\Support\Facades\Artisan;

class RemoveEmptyTermsCommand extends BaseTranslationsCommand
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'ts:remove-empty-terms';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Remove empty terms.';

    protected array $termsToRemove;

    protected function processCommand(): void
    {
        $this->warn('Importing translations from POEditor');
        Artisan::call('ts:import');
        $this->warn('Searching for empty terms.');
        $service = app(LocalizationService::class);
        $termsToRemove = $service->getUnusedTerms();
        if (empty($termsToRemove)) {
            $this->warn('No empty terms found.');
            return;
        }

        $deleted = 0;

        foreach (array_chunk($termsToRemove, 50) as $chunk) {
            $termsGroupToRemove = [];
            foreach ($chunk as $termToRemove) {
                $termsGroupToRemove[] = ['term' => $termToRemove];
            }
            $result = $this->poEditorApiService->deleteTerms($termsGroupToRemove);
            $deleted += $result['deleted'];
        }

        $this->info('Deleted: ' . $deleted);
        $this->warn('Importing translations from POEditor');
        Artisan::call('ts:import');
    }
}
