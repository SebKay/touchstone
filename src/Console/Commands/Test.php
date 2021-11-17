<?php

namespace WPTS\Console\Commands;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Process;

class Test extends Command
{
    protected static $defaultName = 'test';

    protected string $env;
    protected string $root;
    protected string $userProjectRoot;
    protected string $userConfigurationFile;
    protected string $phpunitExecutablePath;
    protected string $phpunitConfigPath;
    protected string $tmpDir;

    public function __construct()
    {
        parent::__construct();

        $this->root                  = __DIR__ . '/../../..';
        $this->userProjectRoot       = \exec('pwd');
        $this->userConfigurationFile = $this->userProjectRoot .'/config.touchstone.php';
        $this->phpunitExecutablePath = $this->userProjectRoot . '/vendor/bin/phpunit';
        $this->phpunitConfigPath     = $this->root . '/phpunit-touchstone.xml';
        $this->tmpDir                = \sys_get_temp_dir();
    }

    public function setEnvironment(string $env): self
    {
        $this->env = $env;

        return $this;
    }

    protected function loadUserConfiguration()
    {
        if (!\file_exists($this->userConfigurationFile)) {
            \ray('Config file DOESNT exist', $this->userConfigurationFile);

            return;
        }

        \ray('Config file exists', $this->userConfigurationFile);
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
        $output->writeln(\WPTS\CMD_INTRO);

        // $this->loadUserConfiguration();

        try {
            $process_args = [
                $this->phpunitExecutablePath,
            ];

            switch ($input->getOption('type')) {
                case 'all':
                    $process_args[] = $this->userProjectRoot . '/tests';
                    break;
                case 'unit':
                    $process_args[] = $this->userProjectRoot . '/tests/Unit';
                    break;
                case 'integration':
                    $process_args[] = $this->userProjectRoot . '/tests/Integration';
                    break;
            }

            $process_args[] = '--config';
            $process_args[] = $this->phpunitConfigPath;

            $output->writeln([
                \WPTS\CMD_ICONS['loading'] . " Running {$input->getOption('type')} tests...",
                '',
            ]);

            $this->preTestChecks();

            $process = new Process($process_args);

            $process->setTty(true);

            $process->run(function (string $type, string $buffer) use ($output) {
                if (Process::ERR === $type) {
                    throw new \Exception("There was an error running the tests");
                }
            });

            $output->writeln([
                '',
                \WPTS\CMD_ICONS['check'] . " Tests run successfully",
            ]);

            return Command::SUCCESS;
        } catch (\Throwable $e) {
            $output->writeln([
                '',
                \WPTS\CMD_ICONS['cross'] . " {$e->getMessage()}",
            ]);

            return Command::FAILURE;
        }
    }

    protected function preTestChecks(): void
    {
        $this->verifyTestFilesExist();
    }

    protected function verifyTestFilesExist(): void
    {
        $wp_files_root   = $this->tmpDir . '/wordpress';
        $test_files_root = $this->tmpDir . '/wordpress-tests-lib';

        if (!\is_dir($wp_files_root)) {
            throw new \InvalidArgumentException("Cannot find WordPress folder. Please run setup command.");
        }

        if (!\is_dir($test_files_root)) {
            throw new \InvalidArgumentException("Cannot find WordPress test folder. Please run setup command.");
        }

        if (!\file_exists($test_files_root . '/includes/functions.php')) {
            throw new \InvalidArgumentException("Cannot find WordPress test files. Please run setup command.");
        }
    }

    protected function verifyTestConfigExists(): void
    {
        if (!\file_exists($this->phpunitExecutablePath)) {
            throw new \InvalidArgumentException("Cannot find PHPUnit config file. Please create a phpunit.xml file.");
        }
    }
}
