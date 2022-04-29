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
        Schema::table('trans_expense_claim_details', function (Blueprint $table) {
            if(!Schema::hasColumn('trans_expense_claim_details', 'is_exceed_limit')){
                $table->boolean('is_exceed_limit')->after('is_bp_approval')->default(0);
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
        Schema::table('trans_expense_claim_details', function (Blueprint $table) {
            //
        });
    }
};
