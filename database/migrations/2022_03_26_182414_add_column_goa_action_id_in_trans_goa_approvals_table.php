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
        Schema::table('trans_goa_approvals', function (Blueprint $table) {
            if(!Schema::hasColumn('trans_goa_approvals', 'goa_action_id')){
                $table->unsignedBigInteger('goa_action_id')->nullable()->after('goa_delegation_id');
                $table->foreign('goa_action_id')
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
        
    }
};
