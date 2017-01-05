<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddPullRequestColumnToFormulasTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        DB::table('formulas')
            ->get(['id', 'git_repo'])
            ->each(function ($formula) {
                DB::table('formulas')
                    ->where('id', $formula->id)
                    ->update(['git_repo' => json_encode(['path' => $formula->git_repo])]);
            });

        Schema::table('formulas', function (Blueprint $table) {
            $table->renameColumn('git_repo', 'git');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::table('formulas')
            ->get(['id', 'git'])
            ->each(function ($formula) {
                DB::table('formulas')
                    ->where('id', $formula->id)
                    ->update(['git' => json_decode($formula->git, true)['path']]);
            });

        Schema::table('formulas', function (Blueprint $table) {
            $table->renameColumn('git', 'git_repo');
        });
    }
}
