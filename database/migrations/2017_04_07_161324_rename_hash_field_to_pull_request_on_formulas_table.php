<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class RenameHashFieldToPullRequestOnFormulasTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('formulas', function (Blueprint $table) {
            $table->renameColumn('hash', 'pull_request');
        });

        DB::table('formulas')->update(['pull_request' => null]);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('formulas', function (Blueprint $table) {
            $table->renameColumn('pull_request', 'hash');
        });

        DB::table('formulas')->update(['hash' => null]);
    }
}
