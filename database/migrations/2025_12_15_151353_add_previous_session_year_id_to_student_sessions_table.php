<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('student_sessions', function (Blueprint $table) {
            $table->unsignedBigInteger('previous_session_year_id')
                ->nullable()
                ->after('session_year_id');
        });
    }

    public function down(): void
    {
        Schema::table('student_sessions', function (Blueprint $table) {
            $table->dropColumn('previous_session_year_id');
        });
    }
};
