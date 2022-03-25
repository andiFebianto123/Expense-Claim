<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTransGoaApprovalsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('trans_goa_approvals', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('expense_claim_id');
            $table->unsignedBigInteger('goa_id');
            $table->unsignedBigInteger('goa_delegation_id')->nullable();
            $table->boolean('is_admin_delegation');
            $table->date('start_approval_date')->nullable();
            $table->date('goa_date')->nullable();
            $table->string('status');
            $table->unsignedBigInteger('order');

            $table->timestamps();

            $table->foreign('expense_claim_id')
            ->references('id')
            ->on('trans_expense_claims')
            ->onUpdate('cascade');

            $table->foreign('goa_id')
            ->references('id')
            ->on('mst_users')
            ->onUpdate('cascade');


            $table->foreign('goa_delegation_id')
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
        Schema::dropIfExists('trans_goa_approvals');
    }
}
