<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected array $tables = [
        'lessons',
        'assignments',
        'announcements',
        'class_teachers',
        'subject_teachers',
        'exams',
        'online_exams'
    ];

    public function up(): void
    {
        foreach ($this->tables as $tableName) {
            Schema::table($tableName, function (Blueprint $table) {
                $table->unsignedBigInteger('semester_id')->nullable()->after('id');

                $table->foreign('semester_id')
                    ->references('id')
                    ->on('semesters')
                    ->onDelete('cascade');
            });
        }
    }

    public function down(): void
    {
        foreach ($this->tables as $tableName) {
            Schema::table($tableName, function (Blueprint $table) use ($tableName) {
                if (Schema::hasColumn($tableName, 'semester_id')) {
                    $table->dropForeign(['semester_id']);
                    $table->dropColumn('semester_id');
                }
            });
        }
    }
};
