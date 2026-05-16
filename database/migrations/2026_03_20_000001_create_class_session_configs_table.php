<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('class_session_configs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('class_id')
                ->constrained('classes')
                ->cascadeOnDelete();
            $table->foreignId('session_year_id')
                ->constrained('session_years')
                ->cascadeOnDelete();
            $table->boolean('include_semesters')
                ->default(false)
                ->comment('0 = no, 1 = yes');
            $table->timestamps();

            $table->unique(
                ['class_id', 'session_year_id'],
                'class_session_configs_class_session_unique'
            );
            $table->index(
                ['class_id', 'session_year_id'],
                'class_session_configs_class_session_index'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('class_session_configs');
    }
};
