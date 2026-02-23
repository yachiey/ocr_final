<?php

namespace App\Models;

use MongoDB\Laravel\Eloquent\Model;

class OcrResult extends Model
{
    protected $connection = 'mongodb';

    protected $collection = 'ocr_results';

    protected $fillable = [
        'user_id',
        'store_name',
        'date',
        'total_amount',
        'items',
        'raw_text',
        'image_path',
        'merchant',
        'transaction',
        'totals',
        'payment',
        'lines',
    ];

    protected $casts = [
        'user_id' => 'integer',
        'total_amount' => 'float',
    ];
}
