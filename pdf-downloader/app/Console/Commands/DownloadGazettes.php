<?php

namespace App\Console\Commands;

use App\Services\EditionService;
use App\Services\PdfUrlBuilder;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use App\Services\PdfDownloader;
use App\Models\Gazette;
use Illuminate\Support\Facades\Log;

#[Signature('pdf:download')]
#[Description('Download gazette PDFs from the last 7 days')]
class DownloadGazettes extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(
        EditionService $editionService,
        PdfUrlBuilder $pdfUrlBuilder,
        PdfDownloader $pdfDownloader
    ) {
        $today = now();

        for ($i = 6; $i >= 0; $i--) {
            $date = $today->copy()->subDays($i);

            $formattedDate = $date->format('d/m/Y');
            $editionDate = $date->format('Y-m-d');

            try {
                $exists = $editionService->editionExists($formattedDate);

                if (!$exists) {
                    $this->info(
                        $formattedDate . ' → pdf does not exist'
                    );

                    Log::info('Gazette edition does not exist', [
                        'edition_date' => $editionDate,
                    ]);

                    continue;
                }

                $pdfUrl = $pdfUrlBuilder->build($formattedDate);

                $this->info(
                    $formattedDate . ' → exists'
                );

                $this->line(
                    'PDF: ' . $pdfUrl
                );

                Log::info('Gazette edition found', [
                    'edition_date' => $editionDate,
                    'pdf_url' => $pdfUrl,
                ]);

                $filename = $editionDate . '.pdf';

                $path = storage_path(
                    'app/downloads/' . $filename
                );

                $download = $pdfDownloader->download(
                    $pdfUrl,
                    $path
                );

                if ($download['action'] === 'skipped') {
                    $this->info(
                        'Already complete. Skipping download.'
                    );

                    Log::info('Gazette PDF already complete', [
                        'edition_date' => $editionDate,
                        'file_path' => $path,
                        'file_size' => $download['size'],
                    ]);
                } elseif ($download['action'] === 'resumed') {
                    $this->info(
                        'Download resumed: ' . $path
                    );

                    Log::info('Gazette PDF download resumed', [
                        'edition_date' => $editionDate,
                        'file_path' => $path,
                        'file_size' => $download['size'],
                    ]);
                } else {
                    $this->info(
                        'Downloaded: ' . $path
                    );

                    Log::info('Gazette PDF downloaded', [
                        'edition_date' => $editionDate,
                        'file_path' => $path,
                        'file_size' => $download['size'],
                    ]);
                }

                // Gazette::create([
                //     'edition_date' => $date->format('Y-m-d'),
                //     'pdf_url' => $pdfUrl,
                //     'file_path' => $download['path'],
                //     'status' => 'downloaded',
                //     'file_size' => $download['size'],
                // ]);

                //for future use Does edition_date exist?
                //        │
                //    ┌───┴────┐
                //   YES       NO
                //    │         │
                //  UPDATE     CREATE

                $gazette = Gazette::updateOrCreate(
                    [
                        'edition_date' => $editionDate,
                    ],
                    [
                        'pdf_url' => $pdfUrl,
                        'file_path' => $download['path'],
                        'status' => 'downloaded',
                        'file_size' => $download['size'],
                    ]
                );

                if ($gazette->wasRecentlyCreated) {
                    $this->info('Database record created.');
                } else {
                    $this->info('Database record already exists.');
                }
                $this->line('========================================================================================' . PHP_EOL);

                Log::info('Gazette database record saved', [
                    'edition_date' => $editionDate,
                    'file_path' => $download['path'],
                    'file_size' => $download['size'],
                ]);

            } catch (\Throwable $e) {

                $this->error(
                    'Download failed: ' . $e->getMessage()
                );

                Log::error('Gazette processing failed', [
                    'edition_date' => $editionDate,
                    'error' => $e->getMessage(),
                    'exception' => get_class($e),
                ]);

                continue;
            }
        }
    }
}