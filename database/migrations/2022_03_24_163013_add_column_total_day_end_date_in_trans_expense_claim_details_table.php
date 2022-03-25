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
            if(!Schema::hasColumn('trans_expense_claim_details', 'end_date')){
                $table->date('end_date')->nullable()->after('total_person');
            }
            if(!Schema::hasColumn('trans_expense_claim_details', 'total_day')){
                $table->unsignedBigInteger('total_day')->nullable()->after('total_person');
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
