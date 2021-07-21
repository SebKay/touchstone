<?php
namespace WPTS\Console\Commands;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class Setup extends Command
{
    protected static $defaultName = 'setup';

    protected function configure(): void
    {
        $this->setDescription('Install WordPress test files (and can optionally create the test database.');

        $this->addOption(
            'db-host',
            null,
            InputOption::VALUE_REQUIRED,
            "What's the host name of the database?"
        );

        $this->addOption(
            'db-name',
            null,
            InputOption::VALUE_REQUIRED,
            "What's the name of the database?"
        );

        $this->addOption(
            'db-user',
            null,
            InputOption::VALUE_REQUIRED,
            "What's the user for the database?"
        );

        $this->addOption(
            'db-pass',
            null,
            InputOption::VALUE_REQUIRED,
            "What's the password of the user for the database?"
        );

        $this->addOption(
            'skip-db-creation',
            null,
            InputOption::VALUE_NONE,
            "Skip the creation of the database if it already exists"
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $output->writeln(\WPTS_CMD_INTRO);

        $creds = [
            'host' => $input->getOption('db-host') ?: '',
            'name' => $input->getOption('db-name') ?: '',
            'user' => $input->getOption('db-user') ?: '',
            'pass' => $input->getOption('db-pass') ?: '',
        ];

        try {
            $this->verifyDatabaseCreds($creds);

            // throw new \Exception("There was an unknown problem installing the test files.");

            $output->writeln(\WPTS_CMD_ICONS['loading'] . ' Downloading WordPress...');
            $output->writeln(\WPTS_CMD_ICONS['loading'] . ' Installing WordPress...');
            $output->writeln(\WPTS_CMD_ICONS['loading'] . ' Installing test files...');

            if (!$input->getOption('skip-db-creation')) {
                $output->writeln(\WPTS_CMD_ICONS['loading'] . ' Creating database...');
            }
            
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
    }

    protected function verifyDatabaseCreds(array $creds)
    {
        if ($creds['host'] == '') {
            throw new \Exception("Please provide a database host.");
            
            return Command::INVALID;
        }

        if ($creds['name'] == '') {
            throw new \Exception("Please provide a database name.");
            
            return Command::INVALID;
        }

        if ($creds['user'] == '') {
            throw new \Exception("Please provide a database user.");
            
            return Command::INVALID;
        }

        if ($creds['pass'] == '') {
            throw new \Exception("Please provide a database password.");
            
            return Command::INVALID;
        }
    }
}
