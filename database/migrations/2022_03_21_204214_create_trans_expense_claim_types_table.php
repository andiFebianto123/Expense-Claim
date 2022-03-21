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
        Schema::create('trans_expense_claim_types', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('expense_claim_id');
            $table->unsignedBigInteger('expense_type_id');
            $table->string('expense_name');

            $table->unsignedBigInteger('level_id');
            $table->string('detail_level_id');
            $table->string('level_name');

            $table->unsignedBigInteger('limit')->nullable();

            $table->unsignedBigInteger('expense_code_id');
            $table->string('account_number');
            $table->string('description');

            $table->boolean('is_traf');
            $table->boolean('is_bod');
            $table->boolean('is_bp_approval');
            $table->boolean('is_limit_person');
            $table->string('currency');
            $table->unsignedBigInteger('limit_business_approval')->nullable();
            $table->string('remark_expense_type')->nullable();

            $table->timestamps();

            $table->foreign('expense_claim_id')
            ->references('id')
            ->on('trans_expense_claims')
            ->onUpdate('cascade');

            $table->foreign('level_id')
            ->references('id')
            ->on('mst_levels')
            ->onUpdate('cascade');

            $table->foreign('expense_type_id')
                ->references('id')
                ->on('mst_expense_types')
                ->onUpdate('cascade');

            $table->foreign('expense_code_id')
                ->references('id')
                ->on('mst_expense_codes')
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
        Schema::dropIfExists('expense_claim_types');
    }
};
