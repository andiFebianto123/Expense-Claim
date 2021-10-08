<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateApprovalCardsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('approval_cards', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('level_id');
            $table->string('level_type');
            $table->unsignedBigInteger('limit')->nullable();
            $table->string('currency');
            $table->string('remark')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('approval_cards');
    }
}
