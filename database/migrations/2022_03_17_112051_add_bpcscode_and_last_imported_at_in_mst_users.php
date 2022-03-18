<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddBpcscodeAndLastImportedAtInMstUsers extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('mst_users', function (Blueprint $table) {
            if(!Schema::hasColumn('mst_users', 'bpcscode')){
                $table->string('bpcscode')->nullable()->after('bpid');
            }
            if(!Schema::hasColumn('mst_users', 'last_imported_at')){
                $table->timestamp('last_imported_at')->nullable()->after('remark');
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
        Schema::table('mst_users', function (Blueprint $table) {
            //
        });
    }
};
