<?php

namespace WPTS\Console\Commands;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Process;
use WPTS\TestsSettings;
use WPTS\UserConfiguration;

class Test extends Command
{
    protected static $defaultName = 'test';

    protected string $appRoot;
    protected string $consumerRoot;
    protected string $consumerConfigurationFile;
    protected string $phpunitExecutablePath;
    protected string $phpunitConfigPath;
    protected string $tmpDir;

    public function __construct()
    {
        parent::__construct();

        $this->appRoot                   = __DIR__ . '/../../..';
        $this->consumerRoot              = \exec('pwd');
        $this->consumerConfigurationFile = $this->consumerRoot .'/config.touchstone.php';
        $this->phpunitExecutablePath     = $this->consumerRoot . '/vendor/bin/phpunit';
        $this->phpunitConfigPath         = $this->appRoot . '/phpunit-touchstone.xml';
        $this->tmpDir                    = \sys_get_temp_dir();
    }

    protected function userConfiguration(): UserConfiguration
    {
        if (!\file_exists($this->consumerConfigurationFile)) {
            return [];
        }

        $config = include $this->consumerConfigurationFile;

        if (!\is_array($config)) {
            return [];
        }

        return new UserConfiguration($config);
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

        $settings = new TestsSettings();

        $settings->testsDir            = $this->consumerRoot . '/' . ($this->userConfiguration()->getTestsDirectory() ?: 'tests');
        $settings->unitTestsDir        = $this->consumerRoot . '/' . ($this->userConfiguration()->getUnitTestsDirectory() ?: 'tests/Unit');
        $settings->integrationTestsDir = $this->consumerRoot . '/' . ($this->userConfiguration()->getIntegrationTestsDirectory() ?: 'tests/Integration');

        try {
            $process_args = [
                $this->phpunitExecutablePath,
            ];

            switch ($input->getOption('type')) {
                case 'all':
                    $test_type_text = 'All';
                    $process_args[] = $settings->testsDir;
                    break;
                case 'unit':
                    $test_type_text = 'Unit';
                    $process_args[] = $settings->unitTestsDir;
                    break;
                case 'integration':
                    $test_type_text = 'Integration';
                    $process_args[] = $settings->integrationTestsDir;
                    break;
                default:
                    $test_type_text = '';
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
                \WPTS\CMD_ICONS['check'] . " {$test_type_text} tests finished running",
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
