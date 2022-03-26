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
        Schema::table('trans_expense_claims', function (Blueprint $table) {
            if(!Schema::hasColumn('trans_expense_claims', 'hod_status')){
                $table->string('hod_status')->default('-')->after('hod_id');
            }
            if(!Schema::hasColumn('trans_expense_claims', 'hod_action_id')){
                $table->unsignedBigInteger('hod_action_id')->nullable()->after('hod_delegation_id');
                $table->foreign('hod_action_id')
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
