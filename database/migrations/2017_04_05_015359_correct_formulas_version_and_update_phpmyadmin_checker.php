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
                $map = [
                    'Github' => 'GithubVersionPrefixV',
                    'Phar' => 'PharVersionPrefixV',
                ];

                $formula->update([
                    'checker' => $map[$formula->getAttribute('checker')] ?? $formula->getAttribute('checker'),
                    'version' => substr($formula->getAttribute('version'), 1),
                ]);
            });

        // rename PharVersion checker
        Formula::where('checker', 'PharVersion')
            ->update([
                'checker' => 'PharFilenameWithVersion',
            ]);
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
        Formula::whereIn('checker', ['GithubVersionPrefixV', 'PharVersionPrefixV'])
            ->get()
            ->each(function (Formula $formula) {
                $map = [
                    'GithubVersionPrefixV' => 'Github',
                    'PharVersionPrefixV' => 'Phar',
                ];

                $formula->update([
                    'checker' => $map[$formula->getAttribute('checker')],
                    'version' => 'v'.$formula->getAttribute('version'),
                ]);
            });

        // rename PharFilenameWithVersion checker
        Formula::where('checker', 'PharFilenameWithVersion')
            ->update([
                'checker' => 'PharVersion',
            ]);
    }
}
