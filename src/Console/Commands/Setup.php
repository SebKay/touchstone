<?php

// src/Command/CreateUserCommand.php

namespace SebKay\Touchstone\Console\Commands;

use GuzzleHttp\Client as Http;

use League\Flysystem\Filesystem;
use League\Flysystem\Local\LocalFilesystemAdapter;
use SebKay\Touchstone\SQLiteConnection;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

use function Laravel\Prompts\error;

use function Laravel\Prompts\info;
use function Laravel\Prompts\note;
use function Laravel\Prompts\password;
use function Laravel\Prompts\spin;
use function Laravel\Prompts\text;

use const SebKay\Touchstone\SQLITE_FILE_PATH;

#[AsCommand(name: 'setup')]
class Setup extends Command
{
    protected string $tempDir;

    protected Filesystem $filesystem;

    public function __construct(
        protected Http $http = new Http(),
    ) {
        parent::__construct();

        $this->tempDir = \sys_get_temp_dir();
        $this->filesystem = new Filesystem(new LocalFilesystemAdapter($this->tempDir));
    }

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

        note('===== Touchstone =====');

        try {
            $this->createDatabase();
            $this->deleteWordPressFiles();
            $this->downloadWordPressFiles();
        } catch (\Throwable $e) {
            error($e->getMessage());

            error($e);

            return Command::FAILURE;
        }

        // \ray($this->tempDir);

        // try {
        //     $pdo = (new SQLiteConnection())->connect();
        //     // create table
        //     $tableName = 'touchstone_'.\random_int(1000, 9999);
        //     $pdo->exec("CREATE TABLE $tableName (id INTEGER PRIMARY KEY, name TEXT)");

        //     // get list of tables
        //     $tables = $pdo->query("SELECT name FROM sqlite_master WHERE type='table' ORDER BY name")->fetchAll(\PDO::FETCH_COLUMN);
        //     \ray($tables);
        // } catch (\Exception $e) {
        //     \ray($e)->red();

        //     return Command::FAILURE;
        // }

        return Command::SUCCESS;
    }

    protected function createDatabase(): void
    {
        spin(function () {
            if (file_exists(SQLITE_FILE_PATH)) {
                \unlink(SQLITE_FILE_PATH);
            }

            \touch(SQLITE_FILE_PATH);
        }, 'Creating database...');

        info('✓  Database created');
    }

    protected function latestWordPressDownloadUrl()
    {
        $response = $this->http->get('https://api.wordpress.org/core/version-check/1.7/');
        $data = \json_decode($response->getBody()->getContents());

        if ($response->getStatusCode() != 200 || ! \property_exists($data, 'offers')) {
            throw new \Exception('There was an error getting the latest version of WordPress. Please check your connection.');
        }

        return $data->offers[0]?->download ?? null;
    }

    protected function deleteWordPressFiles(): void
    {
        spin(function () {
            try {
                $this->filesystem->deleteDirectory('touchstone-wordpress');
            } catch (\Throwable $e) {
                throw new \Exception('Unable to delete old WordPress test files.');
            }
        }, 'Deleteing old WordPress files...');

        info('✓  Old WordPress files deleted');
    }

    protected function downloadWordPressFiles()
    {
        spin(function () {
            try {
                $response = $this->http->get($this->latestWordPressDownloadUrl());
            } catch (\Throwable $e) {
                throw new \Exception('Unable to download WordPress files.');
            }

            \file_put_contents($this->tempDir.'/touchstone-wordpress.zip', $response->getBody()->getContents());
        }, 'Downloading WordPress...');

        info('✓  WordPress downloaded');
    }

    protected function deleteWordPressTestFiles(): void
    {
        try {
            $this->filesystem->deleteDirectory('wordpress-tests-lib');
        } catch (\Throwable $e) {
            throw new \Exception('Unable to delete old WordPress test files.');
        }
    }

    protected function downloadWordPressTestFiles()
    {
        try {
            $response = $this->http->get('https://github.com/WordPress/wordpress-develop/archive/refs/heads/master.zip');
        } catch (\Throwable $e) {
            throw new \Exception('Unable to download WordPress test files.');
        }

        \file_put_contents($this->tempDir.'/wordpress-develop.zip', $response->getBody()->getContents());
    }
}
