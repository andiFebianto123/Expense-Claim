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
            $sm = Schema::getConnection()->getDoctrineSchemaManager();
            $indexesFound = $sm->listTableIndexes('trans_expense_claim_details');
            if(!array_key_exists("trans_expense_claim_details_date_index", $indexesFound)){
                $table->index(['expense_type_id', 'date', 'expense_claim_id'], 'trans_expense_claim_details_date_index');
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
