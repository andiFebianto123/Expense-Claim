<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateExpenseClaimsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('expense_claims', function (Blueprint $table) {
            $table->id();
            $table->string('expense_number')->unique()->nullable();
            $table->unsignedBigInteger('value');
            $table->string('currency')->nullable();

            $table->date('request_date')->nullable();
            $table->unsignedBigInteger('request_id');
            $table->unsignedBigInteger('department_id')->nullable();

            $table->unsignedBigInteger('approval_temp_id')->nullable();
            $table->unsignedBigInteger('approval_id')->nullable();
            $table->date('approval_date')->nullable();

            $table->unsignedBigInteger('goa_temp_id');
            $table->unsignedBigInteger('goa_id')->nullable();
            $table->date('goa_date')->nullable();

            $table->unsignedBigInteger('finance_id')->nullable();
            $table->date('finance_date')->nullable();

            $table->string('status');
            $table->string('remark')->nullable();

            $table->unsignedBigInteger('rejected_id')->nullable();
            $table->date('rejected_date')->nullable();

            $table->unsignedBigInteger('canceled_id')->nullable();
            $table->date('canceled_date')->nullable();

            $table->timestamps();

            $table->foreign('request_id')
            ->references('id')
            ->on('users')
            ->onUpdate('cascade');

            $table->foreign('department_id')
            ->references('id')
            ->on('departments')
            ->onUpdate('cascade');

            $table->foreign('approval_temp_id')
            ->references('id')
            ->on('users')
            ->onUpdate('cascade');

            $table->foreign('approval_id')
            ->references('id')
            ->on('users')
            ->onUpdate('cascade');

            $table->foreign('goa_temp_id')
            ->references('id')
            ->on('users')
            ->onUpdate('cascade');

            $table->foreign('goa_id')
            ->references('id')
            ->on('users')
            ->onUpdate('cascade');

            $table->foreign('finance_id')
            ->references('id')
            ->on('users')
            ->onUpdate('cascade');

            $table->foreign('rejected_id')
            ->references('id')
            ->on('users')
            ->onUpdate('cascade');

            $table->foreign('canceled_id')
            ->references('id')
            ->on('users')
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
        Schema::dropIfExists('expense_claims');
    }
}
