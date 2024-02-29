<?php

// src/Command/CreateUserCommand.php

namespace SebKay\Touchstone\Console\Commands;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

use function Laravel\Prompts\text;

#[AsCommand(name: 'setup')]
class Setup extends Command
{
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        // ... put here the code to create the user

        $dbName = text(
            label: "Database name",
            placeholder: 'e.g. wordpress_tests',
            required: true
        );

        \ray($dbName);

        // this method must return an integer number with the "exit status code"
        // of the command. You can also use these constants to make code more readable

        // return this if there was no problem running the command
        // (it's equivalent to returning int(0))
        return Command::SUCCESS;

        // or return this if some error happened during the execution
        // (it's equivalent to returning int(1))
        // return Command::FAILURE;

        // or return this to indicate incorrect command usage; e.g. invalid options
        // or missing arguments (it's equivalent to returning int(2))
        // return Command::INVALID
    }
}
