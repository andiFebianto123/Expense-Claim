<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class ChangeColumnsMstUsers extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('mst_users', function (Blueprint $table) {
                $table->string('vendor_number')->nullable()->change();
                $table->string('user_id')->nullable()->change();
                $table->string('email')->nullable()->change();
                $table->unsignedBigInteger('level_id')->nullable()->change();
                // $table->foreign('level_id')
                // ->references('id')
                // ->on('mst_levels')
                // ->onUpdate('cascade');
                $table->string('password')->nullable()->change();
                $table->unsignedBigInteger('role_id')->nullable()->change();
                $table->unsignedBigInteger('cost_center_id')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        //
    }
};
