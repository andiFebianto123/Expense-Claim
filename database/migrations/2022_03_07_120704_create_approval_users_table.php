<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateApprovalUsersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('approval_users', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->unique();
            $table->unsignedBigInteger('head_department_id');
            $table->unsignedBigInteger('goa_holder_id');
            $table->timestamps();

            $table->foreign('user_id')
            ->references('id')
            ->on('mst_users')
            ->onUpdate('cascade');

            $table->foreign('head_department_id')
            ->references('id')
            ->on('head_departments')
            ->onUpdate('cascade');

            $table->foreign('goa_holder_id')
            ->references('id')
            ->on('goa_holders')
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
        Schema::dropIfExists('approval_users');
    }
}
