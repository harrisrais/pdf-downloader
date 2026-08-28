<?php

namespace App\Services;

use Carbon\Carbon;

class PdfUrlBuilder
{
    private string $baseUrl = 'https://diariooficial.cepe.com.br/1/arquivos/resumoDiario';

    public function build(string $date): string
    {
        $pdfDate = Carbon::createFromFormat('d/m/Y', $date)
            ->format('Y-m-d');

        return "{$this->baseUrl}/{$pdfDate}/{$pdfDate}.pdf";
    }
}