<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class EditionService
{
    public function editionExists(string $date): bool
    {
        $response = Http::get(
            'https://diariooficial.cepe.com.br/diariooficial/public/home/existeDiarioHome',
            [
                'codigoDiario' => 1,
                'dataPublicacao' => $date,
            ]
        );

        return $response->json();
    }
}