<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('students', function (Blueprint $table) {
            $table->foreignId('class_section_id')->nullable()->change();
            $table->bigInteger('class_id')->nullable(true)->unsigned()->after('user_id');
            $table->foreign('class_id')->references('id')->on('classes');
            $table->string('application_type')->nullable()->after('class_section_id')->default('offline');
        });

        DB::table('web_settings')->insert([
            [
                'name'=> 'registration',
                'tag' => 'Student Registration Form',
                'heading' => 'Please complete the form below to enroll in our programs at school. Provide accurate and detailed information to help us process your registration efficiently and ensure you receive the best ',
                'content' => null,
                'image' => null,
                'status' => 1
            ]
        ]);

        Schema::create('semesters', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->integer('start_month');
            $table->integer('end_month');
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::table('classes', function (Blueprint $table) {
            $table->tinyInteger('include_semesters')->comment('0 - no 1 - yes')->default(0)->after('name');
        });

        Schema::table('class_subjects', function (Blueprint $table) {
            $table->bigInteger('semester_id')->nullable(true)->unsigned()->after('class_id');
            $table->foreign('semester_id')->references('id')->on('semesters');
        });

        Schema::table('elective_subject_groups', function (Blueprint $table) {
            $table->bigInteger('semester_id')->nullable(true)->unsigned()->after('class_id');
            $table->foreign('semester_id')->references('id')->on('semesters');
        });

        Schema::table('timetables', function (Blueprint $table) {
            $table->bigInteger('semester_id')->nullable(true)->unsigned()->after('subject_teacher_id');
            $table->foreign('semester_id')->references('id')->on('semesters');
        });

        Schema::table('student_subjects', function (Blueprint $table) {
            $table->bigInteger('semester_id')->nullable(true)->unsigned()->after('class_section_id');
            $table->foreign('semester_id')->references('id')->on('semesters');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('students', function (Blueprint $table) {
            $table->dropForeign(['class_id']);
            $table->dropColumn('class_id');
            $table->dropColumn('application_type');
            $table->foreignId('class_section_id')->nullable(false)->change();
        });

        DB::table('web_settings')->where('name', 'registration')->delete();


        Schema::table('classes', function (Blueprint $table) {
            $table->dropColumn('include_semesters');
        });

        Schema::table('class_subjects', function (Blueprint $table) {
            $table->dropForeign(['semester_id']);
            $table->dropColumn('semester_id');
        });

        Schema::table('elective_subject_groups', function (Blueprint $table) {
            $table->dropForeign(['semester_id']);
            $table->dropColumn('semester_id');
        });

        Schema::table('timetables', function (Blueprint $table) {
            $table->dropForeign(['semester_id']);
            $table->dropColumn('semester_id');
        });

        Schema::table('student_subjects', function (Blueprint $table) {
            $table->dropForeign(['semester_id']);
            $table->dropColumn('semester_id');
        });

        Schema::dropIfExists('semesters');


    }
};
