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
        Schema::table('mst_users', function (Blueprint $table) {
            if(!Schema::hasColumn('mst_users', 'real_department_id')){
                $table->unsignedBigInteger('real_department_id')->nullable()->after('department_id');

                $table->foreign('real_department_id')
                ->references('id')
                ->on('mst_departments')
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
       
    }
};
