<?php

namespace ArmCyber\Localization\Services;

use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;
use Symfony\Component\Process\Process;

class TranslationService
{
    private const CONFIG_PREFIX = 'localization.';
    private const COMPILED_VIEWS_PATH = 'storage/framework/views/';
    private const TEMP_PO_FILENAME = 'terms.po';
    private const TEMP_VIEW_LIST_FILENAME = 'list.txt';
    private const TEMP_EXPORTS_PATH = 'exports/';

    private FilesystemAdapter $storage;

    /**
     * Destructor.
     */
    public function __destruct()
    {
        $this->removeTempStorage();
    }

    /**
     * Get configuration.
     *
     * @param $key
     * @param $default
     * @return mixed
     */
    private function config($key, $default = null)
    {
        return config(self::CONFIG_PREFIX . $key, $default);
    }

    /**
     * Get gettext storage disk.
     *
     * @return FilesystemAdapter
     */
    private function storage(): FilesystemAdapter
    {
        if (!isset($this->storage)) {
            $this->storage = $this->createTempStorage();
        }

        return $this->storage;
    }

    /**
     * Create new temp storage.
     *
     * @return FilesystemAdapter
     */
    private function createTempStorage(): FilesystemAdapter
    {
        $tmpDirName = Str::snake(config('app.name')) . '_localization_' . Str::random();
        $tmpPath = Str::finish(sys_get_temp_dir(), DIRECTORY_SEPARATOR) . $tmpDirName;
        /** @var FilesystemAdapter */
        return Storage::build([
            'driver' => 'local',
            'root' => $tmpPath,
            'throw' => true,
        ]);
    }

    /**
     * Remove temp storage if exists.
     *
     * @return void
     */
    private function removeTempStorage(): void
    {
        if (isset($this->storage) && $this->storage->exists('/')) {
            $this->storage->deleteDirectory('/');
        }
    }

    /**
     * Get temp export path.
     *
     */
    public function getTempExportsPath(): string
    {
        return $this->storage()->path(self::TEMP_EXPORTS_PATH);
    }

    /**
     * Get temp terms file name.
     *
     * @return string
     */
    public function getTempTermsFile(): string
    {
        return $this->storage()->path(self::TEMP_PO_FILENAME);
    }

    /**
     * Run command in project.
     *
     * @param string $command
     * @param bool $artisan
     *
     * @return string
     */
    public function runCommand(string $command, bool $artisan = false): string
    {
        if ($artisan) {
            Artisan::call($command);
            return Artisan::output();
        }
        $process = Process::fromShellCommandline($command, base_path());
        return $process->mustRun()->getOutput();
    }

    /**
     * Get locales path.
     *
     * @return string
     */
    public function getLocalesPath(): string
    {
        return App::langPath() . '/' . $this->config('translations_path_name', 'i18n')  . '/';
    }

    /**
     * Create list of scanned file to use in xgettext.
     *
     * @return void
     */
    public function createFileList(): void
    {
        $list = [];
        $paths = $this->config('extractor.source_paths', []);
        $paths[] = App::storagePath('framework/views');
        foreach ($paths as $path) {
            if (!File::exists($path) || !File::isDirectory($path)) {
                continue;
            }
            $files = File::allFiles($path);
            foreach ($files as $file) {
                $realPath = $file->getRealPath();
                if (Str::endsWith($realPath, '.blade.php')) {
                    $compiledFileName = base_path() . self::COMPILED_VIEWS_PATH . sha1($realPath) . '.php';
                    if (file_exists($compiledFileName)) {
                        $realPath = $compiledFileName;
                    }
                }
                // Add to list if not exists.
                if (!in_array($realPath, $list) && Str::endsWith($realPath, '.php')) {
                    $list[] = $realPath;
                }
            }
        }
        $this->storage()->put(self::TEMP_VIEW_LIST_FILENAME, implode("\n", $list));
    }

    /**
     * Extract terms using code.
     *
     * @return void
     */
    public function extractTerms(): void
    {
        $keywordsList = $this->config('extractor.keywords_list', []);
        $keywords = collect($keywordsList)->map(function ($keyword) {
            return '--keyword=' . $keyword;
        })->implode(' ');
        $commandArray = [
            'xgettext',
            '--files-from=' . $this->storage()->path(self::TEMP_VIEW_LIST_FILENAME),
            $keywords,
            '--from-code=' . $this->config('encoding', 'UTF-8'),
            '-o ' . $this->getTempTermsFile(),
        ];
        $command = implode(' ', $commandArray);
        $this->runCommand($command);
    }

    /**
     * Store imported file.
     *
     * @param string $importedFile
     * @param string $locale
     * @param string $filename
     * @return void
     */
    public function storeTranslation(string $importedFile, string $locale, string $filename): void
    {
        $path = $this->getTempExportsPath() . $importedFile;
        $localesPath = $this->getLocalesPath();
        $directory = $localesPath . $locale . '/' . $this->config('translations_sub_path_name', 'LC_MESSAGES') . '/';
        $destination = $directory . $filename;

        if (!File::isDirectory($directory) && (!File::isDirectory(App::langPath()) || !mkdir($directory, 0775, true))) {
            throw new RuntimeException("Can't create locales path, ensure that land directory exists and is writable.");
        }

        File::copy($path, $destination);
    }

    /**
     * When using NFS, synced folders may be cached, scandir forces to update mounted folder.
     *
     * @return void
     */
    public function clearNfsCacheForLocalesPath(): void
    {
        @scandir($this->getLocalesPath());
    }
}
