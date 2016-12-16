<?php

namespace App\Console\Commands\Formulas;

use Illuminate\Console\Command;

abstract class Formula extends Command
{
    /**
     * @var \App\Models\Formula
     */
    protected $formula;

    /**
     * Create a new command instance.
     *
     * @param \App\Models\Formula $formula
     */
    public function __construct(\App\Models\Formula $formula)
    {
        parent::__construct();

        $this->formula = $formula;
    }

    /**
     * Instance the checker or get class name.
     *
     * @param \App\Models\Formula|string $formula
     * @param bool $instance
     *
     * @return \App\Checkers\Checker|string
     */
    protected function checker($formula, $instance = true)
    {
        $class = 'App\Checkers\\';

        if (is_string($formula)) {
            return $class.$formula;
        }

        $class .= $formula->getAttribute('checker');

        return $instance ? new $class($formula) : $class;
    }

    /**
     * Execute the console command.
     */
    abstract public function handle();
}
