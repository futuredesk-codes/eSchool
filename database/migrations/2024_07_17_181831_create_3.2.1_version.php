<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('assignment_submissions', function (Blueprint $table) {
            $table->text('text_submission')->after('student_id')->nullable();
        });
        Schema::table('classes', function (Blueprint $table) {
            $table->bigInteger('educational_program_id')->nullable(true)->unsigned()->after('shift_id');
            $table->foreign('educational_program_id')->references('id')->on('educational_programs');
        });
        Schema::table('leaves', function (Blueprint $table) {
            $table->text('reason')->change();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('assignment_submissions', function (Blueprint $table) {
            $table->dropColumn('text_submission');
        });
        Schema::table('classes', function (Blueprint $table) {
            $table->dropForeign(['educational_program_id']);
            $table->dropColumn('educational_program_id');
        });
        Schema::table('leaves', function (Blueprint $table) {
            $table->string('reason')->change();
        });
    }
};
