<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddUserIdInMstDepartments extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('mst_departments', function (Blueprint $table) {
            if(!Schema::hasColumn('mst_departments', 'user_id')){
                $table->unsignedBigInteger('user_id')->nullable()->after('is_none');
                $table->foreign('user_id')
                ->references('id')
                ->on('mst_users')
                ->onUpdate('cascade');
            }
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        // Schema::table('mst_departments', function (Blueprint $table) {
        //     //
        // });
    }
}
