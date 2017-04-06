<?php

use App\Models\Formula;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class RenameIntervalFieldAndModifyUrlFieldOnFormulasTable extends Migration
{
    /**
     * RenameIntervalFieldAndModifyUrlFieldOnFormulasTable constructor.
     */
    public function __construct()
    {
        Formula::flushEventListeners();
    }

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('formulas', function (Blueprint $table) {
            $table->dropIndex(['interval']);
        });

        Schema::table('formulas', function (Blueprint $table) {
            $table->renameColumn('interval', 'enable');
        });

        Schema::table('formulas', function (Blueprint $table) {
            $table->renameColumn('url', 'repo');
        });

        Schema::table('formulas', function (Blueprint $table) {
            $table->boolean('enable')
                ->default(true)
                ->index()
                ->change();
        });

        Formula::all()->each(function (Formula $formula) {
            $formula->update([
                'repo' => str_replace('https://github.com/', '', $formula->getAttribute('repo')),
                'enable' => true,
            ]);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('formulas', function (Blueprint $table) {
            $table->dropIndex(['enable']);
        });

        Schema::table('formulas', function (Blueprint $table) {
            $table->renameColumn('enable', 'interval');
        });

        Schema::table('formulas', function (Blueprint $table) {
            $table->renameColumn('repo', 'url');
        });

        Schema::table('formulas', function (Blueprint $table) {
            $table->unsignedSmallInteger('interval')
                ->default(1)
                ->index()
                ->change();
        });

        Formula::all()->each(function (Formula $formula) {
            $formula->update([
                'url' => 'https://github.com/'.$formula->getAttribute('url'),
            ]);
        });
    }
}
