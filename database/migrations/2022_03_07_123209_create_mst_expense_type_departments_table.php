<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateMstExpenseTypeDepartmentsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('mst_expense_type_departments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('expense_type_id');
            $table->unsignedBigInteger('department_id');
            $table->timestamps();

            $table->foreign('expense_type_id')
            ->references('id')
            ->on('mst_expense_types')
            ->onUpdate('cascade');

            $table->foreign('department_id')
            ->references('id')
            ->on('departments')
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
        Schema::dropIfExists('mst_expense_type_departments');
    }
}
