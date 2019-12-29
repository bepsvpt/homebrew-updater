<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddRevisionAndDependentColumnsToFormulasTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('formulas', function (Blueprint $table) {
            $table->string('revision')->nullable();
            $table->string('dependent')->nullable();
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
            $table->dropColumn('revision');
        });

        Schema::table('formulas', function (Blueprint $table) {
            $table->dropColumn('dependent');
        });
    }
}
