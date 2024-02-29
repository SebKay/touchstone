<?php

// src/Command/CreateUserCommand.php

namespace SebKay\Touchstone\Console\Commands;

use SebKay\Touchstone\SQLiteConnection;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

use function Laravel\Prompts\password;

use function Laravel\Prompts\text;

use const SebKay\Touchstone\SQLITE_FILE_PATH;

#[AsCommand(name: 'setup')]
class Setup extends Command
{
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        // $db = [
        //     'host' => text(
        //         label: 'Database host',
        //         placeholder: 'e.g. localhost',
        //         default: 'localhost',
        //         required: true
        //     ),
        //     'socket' => text(
        //         label: 'Database socket',
        //         placeholder: 'e.g. 8888',
        //     ),
        //     'name' => text(
        //         label: 'Database name',
        //         placeholder: 'e.g. wordpress_tests',
        //         required: true
        //     ),
        //     'user' => text(
        //         label: 'Database user',
        //         placeholder: 'e.g. root',
        //         default: 'root',
        //         required: true
        //     ),
        //     'password' => password(
        //         label: 'Database password',
        //     ),
        // ];

        // \ray($db);

        \unlink(SQLITE_FILE_PATH);
        \touch(SQLITE_FILE_PATH);

        try {
            $pdo = (new SQLiteConnection())->connect();
            // create table
            $tableName = 'touchstone_' . \random_int(1000, 9999);
            $pdo->exec("CREATE TABLE $tableName (id INTEGER PRIMARY KEY, name TEXT)");

            // get list of tables
            $tables = $pdo->query("SELECT name FROM sqlite_master WHERE type='table' ORDER BY name")->fetchAll(\PDO::FETCH_COLUMN);
            \ray($tables);
        } catch (\Exception $e) {
            \ray($e)->red();
            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }
}
