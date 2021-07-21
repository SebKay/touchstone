<?php
namespace WPTS\Console\Commands;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class Setup extends Command
{
    protected static $defaultName = 'setup';

    protected function configure(): void
    {
        $this->setDescription('Install WordPress test files (and can optionally create the test database.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $output->writeln(\WPTS_CMD_INTRO);

        try {
            // throw new \Exception("There was an unknown problem installing the test files.");

            $output->writeln(\WPTS_CMD_ICONS['loading'] . ' Downloading WordPress...');
            $output->writeln(\WPTS_CMD_ICONS['loading'] . ' Installing WordPress...');
            $output->writeln(\WPTS_CMD_ICONS['loading'] . ' Installing test files...');
            $output->writeln(\WPTS_CMD_ICONS['loading'] . ' Creating database...');
            
            $output->writeln([
                '',
                \WPTS_CMD_ICONS['check'] . " Setup complete",
            ]);

            return Command::SUCCESS;
        } catch (\Throwable $e) {
            $output->writeln([
                '',
                \WPTS_CMD_ICONS['cross'] . " {$e->getMessage()}",
            ]);

            return Command::FAILURE;
        }

        // or return this if some error happened during the execution
        // (it's equivalent to returning int(1))
        // return Command::FAILURE;

        // or return this to indicate incorrect command usage; e.g. invalid options
        // or missing arguments (it's equivalent to returning int(2))
        // return Command::INVALID
    }
}
