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
        Schema::table('mst_expense_types', function (Blueprint $table) {
            if(!Schema::hasColumn('mst_expense_types', 'limit_monthly')){
                $table->boolean('limit_monthly')->after('currency')->default(0);
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
        Schema::table('mst_expense_types', function (Blueprint $table) {
            //
        });
    }
};
