<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateUsersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('user_id')->unique();
            $table->string('vendor_number');
            $table->string('name');
            $table->string('email')->unique();
            $table->string('bpid')->unique();
            $table->unsignedBigInteger('level_id');
            $table->boolean('is_active');
            $table->string('password');
            $table->unsignedBigInteger('role_id');
            $table->unsignedBigInteger('cost_center_id');
            $table->unsignedBigInteger('department_id')->nullable();
            // $table->unsignedBigInteger('head_department_id')->nullable();
            // $table->unsignedBigInteger('goa_id')->nullable();
            // $table->unsignedBigInteger('respective_director_id')->nullable();
            $table->string('remark')->nullable();
            $table->rememberToken();
            $table->timestamps();

            $table->foreign('department_id')
            ->references('id')
            ->on('departments')
            ->onUpdate('cascade');

            $table->foreign('level_id')
            ->references('id')
            ->on('levels')
            ->onUpdate('cascade');

            $table->foreign('role_id')
            ->references('id')
            ->on('roles')
            ->onUpdate('cascade');

            $table->foreign('cost_center_id')
            ->references('id')
            ->on('cost_centers')
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
        Schema::dropIfExists('users');
    }
}
