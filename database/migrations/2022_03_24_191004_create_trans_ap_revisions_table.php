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
        Schema::create('trans_ap_revisions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('expense_claim_id');
            $table->unsignedBigInteger('ap_finance_id');
            $table->date('ap_finance_date')->nullable();
            $table->string('status');
            $table->timestamps();

            $table->foreign('expense_claim_id')
            ->references('id')
            ->on('trans_expense_claims')
            ->onUpdate('cascade');

            $table->foreign('ap_finance_id')
            ->references('id')
            ->on('mst_users')
            ->onUpdate('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('trans_ap_revisions');
    }
};
