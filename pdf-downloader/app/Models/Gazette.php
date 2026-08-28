<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Gazette extends Model
{
    protected $fillable = [
        'edition_date',
        'pdf_url',
        'file_path',
        'status',
        'file_size',
    ];

    protected $casts = [
        'edition_date' => 'date',
    ];
}