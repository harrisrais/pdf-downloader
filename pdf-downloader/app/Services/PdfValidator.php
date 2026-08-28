<?php

namespace App\Services;

class PdfValidator
{
    public function validate(string $path): bool
    {
        // 1. File must exist
        if (!file_exists($path)) {
            return false;
        }

        // 2. File must not be empty
        if (filesize($path) === 0) {
            return false;
        }

        // 3. PDF files start with "%PDF"
        $handle = fopen($path, 'rb');

        if ($handle === false) {
            return false;
        }

        $header = fread($handle, 4);

        fclose($handle);

        return $header === '%PDF';
    }
}