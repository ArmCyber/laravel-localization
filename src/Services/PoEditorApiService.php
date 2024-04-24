<?php

namespace ArmCyber\Localization\Services;

use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class PoEditorApiService
{
    public const API_BASEURL = 'https://api.poeditor.com/v2/';

    /**
     * Api token of POEditor, to be preloaded.
     *
     * @var string
     */
    private string $apiToken;

    /**
     * Project ID in PoEditor, to be preloaded.
     *
     * @var string|int
     */
    private $projectId;

    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->loadConfiguration();
    }

    /**
     * Load configurations.
     *
     * @return void
     */
    private function loadConfiguration(): void
    {
        $this->apiToken = config('localization.poeditor.poeditor_api_token', '');
        $this->projectId = config('localization.poeditor.poeditor_project_id', '');
    }

    /**
     * Send request to PoEditor.
     *
     * @param string $endpoint
     * @param array $data
     * @param array|null $attachments
     * @return array
     * @throws RequestException
     */
    private function post(string $endpoint, array $data = [], ?array $attachments = null): array
    {
        $url = self::API_BASEURL . $endpoint;
        $data['api_token'] = $this->apiToken;
        $request = Http::acceptJson();
        if (empty($attachments)) {
            $request->asForm();
        } else {
            $request->asMultipart();
            foreach ($attachments as $name => $attachment) {
                $request->attach($name, File::get($attachment), File::basename($attachment));
            }
        }
        $response = $request->post($url, $data)->throw()->json();
        $code = $response['response']['code'] ?? 'N/A';
        if ($code != '200') {
            $message = $response['response']['message'] ?? 'N/A';
            $status = $response['response']['status'] ?? 'N/A';
            throw new RuntimeException("PoEditor sent fail response. Message: {$message}, code: {$code}, status: {$status} ");
        }
        return $response;
    }

    /**
     * Upload terms to PoEditor.
     *
     * @param string $termsFile
     * @param bool $deleteRemovedTerms = false
     * @return array
     * @throws RequestException
     */
    public function uploadTerms(string $termsFile, bool $deleteRemovedTerms = false): array
    {
        $response = $this->post('projects/upload', [
            'id' => $this->projectId,
            'updating' => 'terms',
            'sync_terms' => $deleteRemovedTerms ? 1 : 0,
        ], [
            'file' => $termsFile,
        ]);

        $terms = $response['result']['terms'] ?? [];

        return [
            'parsed' => $terms['parsed'] ?? 0,
            'added' => $terms['added'] ?? 0,
            'deleted' => $terms['deleted'] ?? 0,
        ];
    }

    /**
     * Download translation from PoEditor and store locally.
     *
     * @param string $locale
     * @param string $type
     * @param string $destination
     * @return void
     * @throws RequestException
     */
    public function downloadTranslation(string $locale, string $type, string $destination): void
    {
        $response = $this->post('projects/export', [
            'id' => $this->projectId,
            'language' => $locale,
            'type' => $type,
        ]);

        if (empty($response['result']['url'])) {
            throw new RuntimeException("PoEditor sent empty URL.");
        }

        File::copy($response['result']['url'], $destination);
    }

    public function deleteTerms($termsToRemove): array
    {
        $response = $this->post('terms/delete', [
            'id' => $this->projectId,
            'updating' => 'terms',
            'data' => json_encode($termsToRemove),
        ]);

        $code = $response['response']['code'] ?? 'N/A';

        if ($code != '200') {
            $message = $response['response']['message'] ?? 'N/A';
            $status = $response['response']['status'] ?? 'N/A';
            throw new RuntimeException("PoEditor sent fail response. Message: {$message}, code: {$code}, status: {$status} ");
        }

        return [
            'parsed' => $response['result']['terms']['parsed'] ?? 0,
            'added' => $response['result']['terms']['added'] ?? 0,
            'deleted' => $response['result']['terms']['deleted'] ?? 0,
        ];
    }
}
