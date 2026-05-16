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
        Schema::table('form_fields', function (Blueprint $table) {
           $table->string('for')->nullable()->after('type')->comment('1- student, 2-parent ,3-teacher');
        });
        Schema::table('parents', static function (Blueprint $table) {
            $table->text('dynamic_fields')->after('occupation');
        });
        Schema::table('teachers', static function (Blueprint $table) {
            $table->text('dynamic_fields')->after('qualification');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('form_fields', function (Blueprint $table) {
            $table->dropColumn('for');
        });
        Schema::table('parents', static function (Blueprint $table) {
            $table->dropColumn('dynamic_fields');
        });
        Schema::table('teachers', static function (Blueprint $table) {
            $table->dropColumn('dynamic_fields');
        });
    }
};
