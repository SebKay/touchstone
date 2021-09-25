<?php
namespace WPTS\Console\Commands;

use PHPUnit\TextUI\Command as TextUICommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Formatter\OutputFormatter;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Process;

class Test extends Command
{
    protected static $defaultName = 'test';

    protected $env;
    protected $phpunitExecutablePath;
    protected $phpunitConfigPath;

    public function __construct()
    {
        parent::__construct();

        $this->phpunitExecutablePath = __DIR__ . '/../../../vendor/bin/phpunit';
        $this->phpunitConfigPath     = __DIR__ . '/../../../phpunit.xml';
    }

    public function setEnvironment(string $env)
    {
        $this->env = $env;

        return $this;
    }

    protected function configure(): void
    {
        $this->setDescription('Run all or a single test suite.');

        $this->addOption(
            'type',
            null,
            InputOption::VALUE_REQUIRED,
            "What type of tests do you want to run?",
            'all'
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $output->writeln(\WPTS_CMD_INTRO);

        if ($this->env == 'prod') {
            $this->phpunitExecutablePath = __DIR__ . '/../../../../../../vendor/bin/phpunit';
        }

        try {
            $process_args = [
                $this->phpunitExecutablePath,
                '--config', $this->phpunitConfigPath,
                '--testsuite',
            ];

            if ($input->getOption('type') == 'all') {
                if ($this->env == 'dev') {
                    $process_args[] = 'Unit-dev,Integration-dev';
                } else {
                    $process_args[] = 'Unit,Integration';
                }
            } else {
                switch ($input->getOption('type')) {
                    case 'unit':
                        if ($this->env == 'dev') {
                            $process_args[] = 'Unit-dev';
                        } else {
                            $process_args[] = 'Unit';
                        }
                        break;
                    case 'integration':
                        if ($this->env == 'dev') {
                            $process_args[] = 'Integration-dev';
                        } else {
                            $process_args[] = 'Integration';
                        }
                        break;
                }
            }

            $output->writeln([
                \WPTS_CMD_ICONS['loading'] . " Running {$input->getOption('type')} tests...",
                '',
            ]);

            $process = new Process($process_args);

            $process->setTty(true);

            $process->run(function ($type, $buffer) use ($output) {
                if (Process::ERR === $type) {
                    throw new \Exception("There was an error running the tests");
                }
            });

            $output->writeln([
                '',
                \WPTS_CMD_ICONS['check'] . " Tests run successfully",
            ]);

            return Command::SUCCESS;
        } catch (\Throwable $e) {
            $output->writeln([
                '',
                \WPTS_CMD_ICONS['cross'] . " {$e->getMessage()}",
            ]);

            return Command::FAILURE;
        }
    }
}
