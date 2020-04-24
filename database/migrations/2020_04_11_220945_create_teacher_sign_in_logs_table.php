<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTeacherSignInLogsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('teacher_sign_in_logs', function (Blueprint $table) {
            $table->bigIncrements('kid');
            $table->string('cid');
            $table->string('command');
            $table->double('latitude');
            $table->double('longitude');
            $table->integer('start_time')->nullable();
            $table->integer('end_time')->nullable();
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
        Schema::dropIfExists('teacher_sign_in_logs');
    }
}
