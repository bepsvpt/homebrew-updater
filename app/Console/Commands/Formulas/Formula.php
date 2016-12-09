<?php

namespace App\Console\Commands\Formulas;

use Illuminate\Console\Command;

abstract class Formula extends Command
{
    /**
     * @var \App\Formula
     */
    protected $formula;

    /**
     * Create a new command instance.
     *
     * @param \App\Formula $formula
     */
    public function __construct(\App\Formula $formula)
    {
        parent::__construct();

        $this->formula = $formula;
    }

    /**
     * Execute the console command.
     */
    abstract public function handle();
}
