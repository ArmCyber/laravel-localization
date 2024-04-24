<?php

namespace ArmCyber\Localization\Commands;

use ArmCyber\Localization\Services\TranslationService;
use ArmCyber\Localization\Services\PoEditorApiService;
use Illuminate\Console\Command;
use Symfony\Component\Console\Command\Command as SymfonyCommand;
use Throwable;

abstract class BaseTranslationsCommand extends Command
{
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
     * Float time of command start.
     *
     * @var float
     */
    protected float $startTime;

    /**
     * Get process time.
     *
     * @return int
     */
    private function getProcessTime(): int
    {
        return round(microtime(true) - $this->startTime);
    }

    /**
     * Process the command.
     *
     */
    abstract protected function processCommand();

    /**
     * Execute the console command.
     *
     * @param TranslationService $translationService
     * @param PoEditorApiService $poEditorApiService
     * @return int
     */
    public function handle(TranslationService $translationService, PoEditorApiService $poEditorApiService): int
    {
        $this->startTime = microtime(true);
        $this->translationService = $translationService;
        $this->poEditorApiService = $poEditorApiService;

        try {
            $this->processCommand();
        } catch (Throwable $exception) {
            $this->error('Error: ' . $exception->getMessage());
            $this->info('Command failed. Process took ' . $this->getProcessTime() . ' seconds.');
            return SymfonyCommand::FAILURE;
        }

        $this->info('Completed. Process took ' . $this->getProcessTime() . ' seconds.');
        return SymfonyCommand::SUCCESS;
    }
}
