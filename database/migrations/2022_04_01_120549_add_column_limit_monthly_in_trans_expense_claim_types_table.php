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
        Schema::table('trans_expense_claim_types', function (Blueprint $table) {
            if(!Schema::hasColumn('trans_expense_claim_types', 'limit_monthly')){
                $table->boolean('limit_monthly')->after('limit_daily')->default(0);
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
