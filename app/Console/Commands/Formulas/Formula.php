<?php

namespace App\Console\Commands\Formulas;

use App\Checkers\Checker;
use App\Models\Formula as FormulaModel;
use Illuminate\Console\Command;

abstract class Formula extends Command
{
    /**
     * Formula eloquent model instance.
     *
     * @var FormulaModel
     */
    protected $formula;

    /**
     * Create a new command instance.
     *
     * @param FormulaModel $formula
     */
    public function __construct(FormulaModel $formula)
    {
        parent::__construct();

        $this->formula = $formula;
    }

    /**
     * Instance the checker or get class name.
     *
     * @param FormulaModel|string $formula
     * @param bool $instance
     *
     * @return Checker|string
     */
    protected function checker($formula, $instance = true)
    {
        $class = 'App\Checkers\%s';

        // if $formula is string, we just return the full namespace
        if (is_string($formula)) {
            return sprintf($class, $formula);
        }

        // otherwise, we use `checker` attribute
        $class = sprintf($class, $formula->checker);

        // return full namespace or instance the class
        return $instance ? new $class($formula) : $class;
    }

    /**
     * Execute the console command.
     */
    abstract public function handle();
}
