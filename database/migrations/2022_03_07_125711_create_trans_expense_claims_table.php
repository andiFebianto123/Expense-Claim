<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTransExpenseClaimsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('trans_expense_claims', function (Blueprint $table) {
            $table->id();
            $table->string('expense_number')->unique()->nullable();
            $table->unsignedBigInteger('value');
            $table->string('currency');

            $table->unsignedBigInteger('upper_limit')->nullable();
            $table->unsignedBigInteger('bottom_limit')->nullable();

            $table->unsignedBigInteger('request_id');
            $table->date('request_date')->nullable();

            $table->unsignedBigInteger('secretary_id')->nullable();

            $table->unsignedBigInteger('current_trans_goa_id')->nullable();

            $table->unsignedBigInteger('hod_id')->nullable();
            $table->unsignedBigInteger('hod_delegation_id')->nullable();
            $table->date('start_approval_date')->nullable();
            $table->boolean('is_admin_delegation');
            $table->date('hod_date')->nullable();

            $table->unsignedBigInteger('finance_id')->nullable();
            $table->date('finance_date')->nullable();

            $table->string('status');
            $table->string('remark')->nullable();

            $table->unsignedBigInteger('rejected_id')->nullable();
            $table->date('rejected_date')->nullable();

            $table->unsignedBigInteger('canceled_id')->nullable();
            $table->date('canceled_date')->nullable();

            $table->timestamps();

            $table->index('current_trans_goa_id');

            $table->foreign('request_id')
            ->references('id')
            ->on('mst_users')
            ->onUpdate('cascade');

            $table->foreign('secretary_id')
            ->references('id')
            ->on('mst_users')
            ->onUpdate('cascade');


            $table->foreign('hod_id')
            ->references('id')
            ->on('mst_users')
            ->onUpdate('cascade');

            $table->foreign('hod_delegation_id')
            ->references('id')
            ->on('mst_users')
            ->onUpdate('cascade');


            $table->foreign('finance_id')
            ->references('id')
            ->on('mst_users')
            ->onUpdate('cascade');

            $table->foreign('rejected_id')
            ->references('id')
            ->on('mst_users')
            ->onUpdate('cascade');

            $table->foreign('canceled_id')
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
        Schema::dropIfExists('trans_expense_claims');
    }
}
