<?php

namespace WPTS\Console\Commands;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Process;
use WPTS\ConsumerSettings;
use WPTS\Settings;
use WPTS\TestingSettings;

class Test extends Command
{
    protected static $defaultName = 'test';

    protected Settings $appSettings;

    public function __construct(Settings $settings)
    {
        parent::__construct();

        $this->appSettings = $settings;
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

        // \ray('User plugins', $this->appSettings->consumerSettings()->plugins());

        try {
            $output->writeln([
                \WPTS\CMD_ICONS['loading'] . " Running {$input->getOption('type')} tests...",
                '',
            ]);

            $this->verifyTestFilesExist();

            $process_args = [
                $this->appSettings->phpunitExecutablePath(),
            ];

            switch ($input->getOption('type')) {
                case 'all':
                    $test_type_text = 'All';
                    $process_args[] = $this->appSettings->testsDirectory();
                    break;
                case 'unit':
                    $test_type_text = 'Unit';
                    $process_args[] = $this->appSettings->unitTestsDirectory();
                    break;
                case 'integration':
                    $test_type_text = 'Integration';
                    $process_args[] = $this->appSettings->integrationTestsDirectory();
                    break;
                default:
                    $test_type_text = '';
                    break;
            }

            $process_args[] = '--config';
            $process_args[] = $this->appSettings->phpunitConfigPath();

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
                \WPTS\CMD_ICONS['cross'] . " {$e->getMessage()}",
                '',
            ]);

            return Command::FAILURE;
        }
    }

    protected function verifyTestFilesExist(): void
    {
        $wp_files_root   = $this->appSettings->tempDirectory() . '/wordpress';
        $test_files_root = $this->appSettings->tempDirectory() . '/wordpress-tests-lib';

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
}
