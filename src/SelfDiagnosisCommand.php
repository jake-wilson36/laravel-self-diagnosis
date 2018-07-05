<?php

namespace BeyondCode\SelfDiagnosis;

use Illuminate\Console\Command;
use BeyondCode\SelfDiagnosis\Checks\Check;

class SelfDiagnosisCommand extends Command
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $signature = 'self-diagnosis';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Perform application self diagnosis.';

    private $messages = [];

    public function handle()
    {
        $this->runChecks(config('self-diagnosis.checks', []), 'Running Common Checks');

        $environmentChecks = config('self-diagnosis.development', []);
        if (in_array(app()->environment(), config('self-diagnosis.productionEnvironments'))) {
            $environmentChecks = config('self-diagnosis.production', []);
        }

        $this->runChecks($environmentChecks, 'Environment Specific Checks ('.app()->environment().')');

        if (count($this->messages)) {
            $this->output->writeln('The following checks failed:');

            foreach ($this->messages as $message) {
                $this->output->writeln('<fg=red>'.$message.'</fg=red>');
                $this->output->writeln('');
            }
        } else {
            $this->info('Good job, looks like you are all set up.');
        }
    }

    protected function runChecks(array $checks, string $title)
    {
        $max = count($checks);
        $current = 1;

        $this->output->writeln('|-------------------------------------');
        $this->output->writeln('| '.$title);
        $this->output->writeln('|-------------------------------------');

        foreach ($checks as $check) {
            $checkClass = app($check);

            $this->output->write("<fg=yellow>Running check {$current}/{$max}:</fg=yellow> {$checkClass->name()}...  ");

            $this->runCheck($checkClass);

            $current++;
        }

        $this->output->writeln('');
    }

    protected function runCheck(Check $check)
    {
        if ($check->check()) {
            $this->output->write('<fg=green>✔</fg=green>');
        } else {
            $this->output->write('<fg=red>✘</fg=red>');

            $this->messages[] = $check->message();
        }

        $this->output->write(PHP_EOL);
    }
}