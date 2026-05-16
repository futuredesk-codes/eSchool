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
        Schema::table('leaves', function (Blueprint $table) {
            $table->bigInteger('session_year_id')->nullable()->unsigned()->after('status');
            $table->foreign('session_year_id')->references('id')->on('session_years');
        });

        Schema::table('fees_paids', function (Blueprint $table) {
            $table->float('due_charges', 8, 2)->nullable()->after('total_amount');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('leaves', function (Blueprint $table) {
            $table->dropForeign(['session_year_id']);
            $table->dropColumn('session_year_id');
        });

        Schema::table('fees_paids', function (Blueprint $table) {
            $table->dropColumn('due_charges');
        });
    }
};
