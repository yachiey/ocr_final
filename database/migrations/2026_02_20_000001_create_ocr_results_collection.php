<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use MongoDB\Laravel\Schema\Blueprint;

return new class extends Migration
{
    public function up(): void
    {
        Schema::connection('mongodb')->create('ocr_results', function (Blueprint $collection): void {
            $collection->index('user_id');
            $collection->index('store_name');
            $collection->index('date');
            $collection->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::connection('mongodb')->dropIfExists('ocr_results');
    }
};
