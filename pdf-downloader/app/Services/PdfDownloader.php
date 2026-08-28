<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use RuntimeException;
use Illuminate\Support\Facades\Log;

class PdfDownloader
{
    public function __construct(
        private PdfValidator $validator
    ) {
    }

    public function download(string $url, string $path): array
    {
        $existingSize = file_exists($path)
            ? filesize($path)
            : 0;

        Log::info('PDF download inspection', [
            'url' => $url,
            'path' => $path,
            'existing_size' => $existingSize,
        ]);

        // Get the size of the remote PDF
        $headResponse = Http::timeout(30)
            ->retry(3, 1000)
            ->head($url);

        if (!$headResponse->successful()) {
            throw new RuntimeException(
                'Unable to check remote PDF. HTTP status: '
                . $headResponse->status()
            );
        }

        $remoteSize = (int) $headResponse->header('Content-Length');

        // File already completely downloaded
        if ($existingSize > 0 && $existingSize === $remoteSize) {
            return [
                'path' => $path,
                'size' => $existingSize,
                'action' => 'skipped',
            ];
        }

        Log::info('PDF already complete', [
            'url' => $url,
            'size' => $existingSize,
        ]);

        $action = $existingSize > 0
            ? 'resumed'
            : 'downloaded';

        Log::info('PDF resume requested', [
            'url' => $url,
            'existing_size' => $existingSize,
            'remote_size' => $remoteSize,
        ]);

        $headers = [];

        if ($existingSize > 0 && $existingSize < $remoteSize) {
            $headers['Range'] = 'bytes=' . $existingSize . '-';
        }

        $response = Http::withHeaders($headers)
            ->timeout(120)
            ->retry(3, 1000)
            ->get($url);

        if (!$response->successful()) {
            throw new RuntimeException(
                'PDF download failed. HTTP status: '
                . $response->status()
            );
        }

        if ($existingSize > 0 && $response->status() === 206) {
            file_put_contents(
                $path,
                $response->body(),
                FILE_APPEND
            );
        } else {
            file_put_contents(
                $path,
                $response->body()
            );
        }

        if (!$this->validator->validate($path)) {
            throw new RuntimeException(
                'Downloaded file is not a valid PDF.'
            );
        }

        // Final size check
        $finalSize = filesize($path);

        if ($finalSize !== $remoteSize) {
            throw new RuntimeException(
                "PDF size mismatch. Expected {$remoteSize} bytes, got {$finalSize} bytes."
            );
        }

        return [
            'path' => $path,
            'size' => $finalSize,
            'action' => $action,
        ];
    }
}