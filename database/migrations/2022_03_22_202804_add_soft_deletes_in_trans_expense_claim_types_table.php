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
            if(!Schema::hasColumn('trans_expense_claim_types', 'deleted_at')){
                $table->softDeletes();
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
        Schema::table('trans_expense_claim_types', function (Blueprint $table) {
            //
        });
    }
};
