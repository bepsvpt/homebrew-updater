<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateFormulasTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('formulas', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name', 64)->unique();
            $table->string('url');
            $table->string('checker', 32);
            $table->string('git_repo')->nullable();
            $table->string('version', 32)->nullable();
            $table->string('archive')->nullable();
            $table->string('hash')->nullable();
            $table->unsignedSmallInteger('interval')->default(1)->index();
            $table->timestamp('checked_at')->nullable()->index();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('formulas');
    }
}
