<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('ReceiptOCR');
})->name('home');

Route::get('/receipt-ocr', function () {
    return Inertia::render('ReceiptOCR');
})->name('receipt.ocr');

Route::post('/ocr/extract', [App\Http\Controllers\OcrController::class, 'extract'])->name('cid.extract');
