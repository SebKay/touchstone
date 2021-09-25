<?php
namespace WPTS\Console\Commands;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use League\Flysystem\Filesystem;
use League\Flysystem\Local\LocalFilesystemAdapter;
use WPConfigTransformer;

class Test extends Command
{
    protected static $defaultName = 'test';

    protected $tmp_dir;

    public function __construct()
    {
        parent::__construct();

        $this->tmp_dir = \sys_get_temp_dir();
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

        /**
         * Steps
         * 1.
         */
        $output->writeln(\WPTS_CMD_ICONS['loading'] . ' Running tests...');

        try {
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
