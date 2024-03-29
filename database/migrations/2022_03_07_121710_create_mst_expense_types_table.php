<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateMstExpenseTypesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('mst_expense_types', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('expense_id');
            $table->unsignedBigInteger('level_id');
            $table->unsignedBigInteger('limit')->nullable();
            $table->unsignedBigInteger('expense_code_id');
            $table->boolean('is_traf');
            $table->boolean('is_bod');
            $table->boolean('is_bp_approval');
            $table->boolean('is_limit_person');
            $table->string('currency');
            $table->string('bod_level', 255)->nullable();
            $table->unsignedBigInteger('limit_business_approval')->nullable();
            $table->string('remark')->nullable();
            $table->timestamps();

            $table->foreign('expense_id')
                ->references('id')
                ->on('mst_expenses')
                ->onUpdate('cascade');

            $table->foreign('level_id')
                ->references('id')
                ->on('mst_levels')
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
        Schema::dropIfExists('mst_expense_types');
    }
}
