<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('gazettes', function (Blueprint $table) {
            $table->id();

            $table->date('edition_date')->unique();

            $table->string('pdf_url');

            $table->string('file_path')->nullable();

            $table->string('status')->default('pending');

            $table->unsignedBigInteger('file_size')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('gazettes');
    }
};