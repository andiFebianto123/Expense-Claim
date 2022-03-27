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
            if(!Schema::hasColumn('trans_expense_claims', 'current_trans_goa_delegation_id')){
                $table->unsignedBigInteger('current_trans_goa_delegation_id')->nullable()->after('current_trans_goa_id');
                $table->index('current_trans_goa_delegation_id');
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
