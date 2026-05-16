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
        Schema::create('leave_masters', function (Blueprint $table) {
            $table->id();
            $table->string('total_leave')->comment('Leaves per month');
            $table->string('holiday_days');
            $table->bigInteger('session_year_id')->nullable(true)->unsigned();
            $table->foreign('session_year_id')->references('id')->on('session_years')->onUpdate('cascade')->onDelete('cascade');
            $table->timestamps();
        });
        Schema::create('leaves', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('user_id')->nullable(true)->unsigned();
            $table->foreign('user_id')->references('id')->on('users')->onUpdate('cascade')->onDelete('cascade');
            $table->bigInteger('leave_master_id')->nullable(true)->unsigned();
            $table->foreign('leave_master_id')->references('id')->on('leave_masters')->onUpdate('cascade')->onDelete('cascade');
            $table->string('reason');
            $table->date('from_date');
            $table->date('to_date');
            $table->string('status')->comment('0- pending, 1- approved, 3- rejected');
            $table->timestamps();
        });
        Schema::create('leave_details', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('leave_id')->nullable(true)->unsigned();
            $table->foreign('leave_id')->references('id')->on('leaves')->onUpdate('cascade')->onDelete('cascade');
            $table->date('date');
            $table->string('type');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('leave_details', function (Blueprint $table) {
            $table->dropForeign(['leave_id']);
        });
        Schema::dropIfExists('leave_masters');
        Schema::dropIfExists('leaves');
        Schema::dropIfExists('leave_details');
    }
};
