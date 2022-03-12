<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateMstExpensesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('mst_expenses', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('type_id');
            $table->foreign('type_id')
                ->references('id')
                ->on('mst_expense_types')
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
        Schema::dropIfExists('mst_expenses');
    }
}
