<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateExpenseClaimDetailsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('expense_claim_details', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('expense_claim_id');
            $table->unsignedBigInteger('approval_card_id');
            $table->unsignedBigInteger('level_id');
            $table->string('level_type');
            $table->date('date');
            $table->string('cost_center');
            $table->string('expense_code');
            $table->string('currency');
            $table->unsignedBigInteger('cost');
            $table->string('document', 512)->nullable();
            $table->string('remark')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('expense_claim_id')
            ->references('id')
            ->on('expense_claims')
            ->onUpdate('cascade');

            $table->foreign('approval_card_id')
            ->references('id')
            ->on('approval_cards')
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
        Schema::dropIfExists('expense_claim_details');
    }
}
