<?php

use App\Models\Formula;
use Illuminate\Database\Migrations\Migration;

class TransformCheckerToDatabaseArchiveField extends Migration
{
    /**
     * TransformCheckerToDatabaseArchiveField constructor.
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
        Formula::all()->each(function (Formula $formula) {
            $archive = null;

            switch ($formula->getAttribute('checker')) {
                case 'Github':
                    $archive = 'https://github.com/{owner}/{name}/archive/{version}.tar.gz';
                    break;

                case 'GithubVersionPrefixV':
                    $archive = 'https://github.com/{owner}/{name}/archive/v{version}.tar.gz';
                    break;

                case 'Phar':
                    $archive = 'https://github.com/{owner}/{name}/releases/download/{version}/{name}.phar';
                    break;

                case 'PharFilenameWithVersion':
                    $archive = 'https://github.com/{owner}/{name}/releases/download/{version}/{name}-{version}.phar';
                    break;

                case 'PharVersionPrefixV':
                    $archive = 'https://github.com/{owner}/{name}/releases/download/v{version}/{name}.phar';
                    break;

                default:
                    switch ($formula->getAttribute('name')) {
                        case 'homebrew/php/phpmyadmin':
                            $archive = 'https://files.phpmyadmin.net/phpMyAdmin/{version}/phpMyAdmin-{version}-all-languages.tar.gz';
                            break;

                        case 'homebrew/php/composer':
                            $archive = 'https://getcomposer.org/download/{version}/composer.phar';
                            break;

                        case 'homebrew/php/phpunit':
                            $archive = 'https://phar.phpunit.de/phpunit-{version}.phar';
                            break;

                        case 'homebrew/php/codeception':
                            $archive = 'http://codeception.com/releases/{version}/codecept.phar';
                            break;

                        case 'homebrew/php/phing':
                            $archive = 'https://www.phing.info/get/phing-{version}.phar';
                            break;

                        case 'homebrew/php/phploc':
                            $archive = 'https://phar.phpunit.de/phploc-{version}.phar';
                            break;

                        case 'homebrew/php/phpmd':
                            $archive = 'http://static.phpmd.org/php/{version}/phpmd.phar';
                            break;

                        case 'homebrew/php/deployer':
                            $archive = 'https://deployer.org/releases/{version}/deployer.phar';
                            break;

                        case 'homebrew/php/drupalconsole':
                            $archive = 'https://github.com/{owner}/{name}/releases/download/{version}/{name}.phar';
                            break;
                    }

                    break;
            }

            $formula->update([
                'checker' => 'Github',
                'archive' => $archive,
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
        Formula::all()->each(function (Formula $formula) {
            $checker = null;

            switch ($formula->getAttribute('archive')) {
                case 'https://github.com/{owner}/{name}/archive/{version}.tar.gz':
                    $checker = 'Github';
                    break;

                case 'https://github.com/{owner}/{name}/archive/v{version}.tar.gz':
                    $checker = 'GithubVersionPrefixV';
                    break;

                case 'https://github.com/{owner}/{name}/releases/download/{version}/{name}.phar':
                    $checker = 'Phar';
                    break;

                case 'https://github.com/{owner}/{name}/releases/download/{version}/{name}-{version}.phar':
                    $checker = 'PharFilenameWithVersion';
                    break;

                case 'https://github.com/{owner}/{name}/releases/download/v{version}/{name}.phar':
                    $checker = 'PharVersionPrefixV';
                    break;

                default:
                    switch ($formula->getAttribute('name')) {
                        case 'homebrew/php/phpmyadmin':
                            $checker = 'Phpmyadmin';
                            break;

                        case 'homebrew/php/composer':
                            $checker = 'Composer';
                            break;

                        case 'homebrew/php/phpunit':
                            $checker = 'Phpunit';
                            break;

                        case 'homebrew/php/codeception':
                            $checker = 'Codeception';
                            break;

                        case 'homebrew/php/phing':
                            $checker = 'Phing';
                            break;

                        case 'homebrew/php/phploc':
                            $checker = 'Phploc';
                            break;

                        case 'homebrew/php/phpmd':
                            $checker = 'Phpmd';
                            break;

                        case 'homebrew/php/deployer':
                            $checker = 'Deployer';
                            break;

                        case 'homebrew/php/drupalconsole':
                            $checker = 'DrupalConsole';
                            break;
                    }

                    break;
            }

            if (! is_null($checker)) {
                $formula->update([
                    'checker' => $checker,
                    'archive' => null,
                ]);
            }
        });
    }
}
