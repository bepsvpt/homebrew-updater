<?php

use App\Models\Formula;
use Illuminate\Database\Migrations\Migration;

class CorrectFormulasVersionAndUpdatePhpmyadminChecker extends Migration
{
    /**
     * @var \Illuminate\Database\Eloquent\Model|null
     */
    protected $phpmyadmin;

    /**
     * CorrectFormulasVersionAndUpdatePhpmyadminChecker constructor.
     */
    public function __construct()
    {
        Formula::flushEventListeners();

        $this->phpmyadmin = Formula::where('name', 'homebrew/php/phpmyadmin')->first();
    }

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // update phpmyadmin checker and version
        if (! is_null($this->phpmyadmin)) {
            $this->phpmyadmin->update([
                'checker' => 'Phpmyadmin',
                'version' => str_replace(['RELEASE_', '_'], ['', '.'], $this->phpmyadmin->getAttribute('version')),
            ]);
        }

        // correct formulas version
        Formula::where('version', 'like', 'v%')
            ->get()
            ->each(function (Formula $formula) {
                $formula->update([
                    'version' => substr($formula->getAttribute('version'), 1),
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
        // update phpmyadmin checker and version
        if (! is_null($this->phpmyadmin)) {
            $this->phpmyadmin->update([
                'checker' => 'Github',
                'version' => 'RELEASE_'.str_replace('.', '_', $this->phpmyadmin->getAttribute('version')),
            ]);
        }

        // correct formulas version
        // this can not be reversed
    }
}
