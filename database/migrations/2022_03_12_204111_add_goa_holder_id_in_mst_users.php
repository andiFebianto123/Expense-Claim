<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddGoaHolderIdInMstUsers extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('mst_users', function (Blueprint $table) {
            if(!Schema::hasColumn('mst_users', 'goa_holder_id')){
                $table->string('goa_holder_id')->nullable()->after('department_id');
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
        //
    }
}
