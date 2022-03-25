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
        Schema::table('mst_users', function (Blueprint $table) {
            $table->unsignedBigInteger('role_id')->nullable()->change();
            if(!Schema::hasColumn('mst_users', 'roles')){
                $table->text('roles')->nullable()->after('role_id');
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
        
    }
};
