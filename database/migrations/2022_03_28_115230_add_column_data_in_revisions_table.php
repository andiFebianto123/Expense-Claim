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
        Schema::table('revisions', function (Blueprint $table) {
            if(!Schema::hasColumn('revisions', 'data')){
                $table->mediumText('data')->nullable()->after('new_value');
            }
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('revisions', function (Blueprint $table) {
            //
        });
    }
};
