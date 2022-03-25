<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateGoaHoldersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('goa_holders', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->string('name')->unique();
            $table->unsignedBigInteger('limit')->nullable();
            $table->unsignedBigInteger('head_department_id')->nullable();
            $table->timestamps();


            $table->foreign('user_id')
            ->references('id')
            ->on('mst_users')
            ->onUpdate('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('goa_holders');
    }
}
