<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTransExpenseClaimDetailsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('trans_expense_claim_details', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('expense_claim_id');
            $table->date('date');
       
            $table->unsignedBigInteger('cost_center_id');

            $table->unsignedBigInteger('expense_type_id');

            $table->unsignedInteger('total_person')->nullable();

            $table->boolean('is_bp_approval');

            $table->string('currency');
            $table->string('converted_currency')->nullable();
            $table->double('exchange_value')->nullable();

            $table->double('cost');
            $table->double('converted_cost')->nullable();
            $table->string('remark')->nullable();
            $table->string('document', 500)->nullable();

            $table->foreign('expense_claim_id')
                ->references('id')
                ->on('trans_expense_claims')
                ->onUpdate('cascade');

            $table->foreign('expense_type_id')
                ->references('id')
                ->on('mst_expense_types')
                ->onUpdate('cascade');


            $table->foreign('cost_center_id')
                ->references('id')
                ->on('mst_cost_centers')
                ->onUpdate('cascade');

            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('trans_expense_claim_details');
    }
}
