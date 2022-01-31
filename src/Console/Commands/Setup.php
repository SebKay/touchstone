<?php

namespace WPTS\Console\Commands;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use League\Flysystem\Filesystem;
use League\Flysystem\Local\LocalFilesystemAdapter;
use WPConfigTransformer;

class Setup extends Command
{
    protected static $defaultName = 'setup';

    protected \GuzzleHttp\Client $httpClient;
    protected string $tmp_dir                 = '';
    protected string $wpZipFilename           = 'wordpress.zip';
    protected string $wpDirectoryName         = 'wordpress';
    protected string $wpTestsZipFilename      = 'wordpress-develop.zip';
    protected string $wpTestsUnzippedFilename = 'wordpress-develop-master';
    protected string $wpTestsDirectoryName    = 'wordpress-tests-lib';
    protected \League\Flysystem\Filesystem $filesystem;
    protected array $db_creds = [];
    protected \PDO $db_connection;

    public function __construct()
    {
        parent::__construct();

        $this->httpClient = new \GuzzleHttp\Client();
        $this->tmp_dir    = \sys_get_temp_dir();
        $this->filesystem = new Filesystem(new LocalFilesystemAdapter($this->tmp_dir));
    }

    protected function configure(): void
    {
        $this->setDescription('Install WordPress test files (and can optionally create the test database.');

        $this->addOption(
            'db-host',
            null,
            InputOption::VALUE_OPTIONAL,
            "What's the host name of the database?"
        );

        $this->addOption(
            'db-socket',
            null,
            InputOption::VALUE_OPTIONAL,
            "What's the socket path for connecting to the database?"
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
            InputOption::VALUE_OPTIONAL,
            "Skip creation of the database because it already exists?",
            false
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $output->writeln(\WPTS\CMD_INTRO);

        $this->db_creds = [
            'host'          => $input->getOption('db-host') ?: '',
            'socket'        => $input->getOption('db-socket') ?: '',
            'name'          => $input->getOption('db-name') ?: '',
            'user'          => $input->getOption('db-user') ?: '',
            'pass'          => $input->getOption('db-pass') ?: '',
            'skip_creation' => $input->getOption('skip-db-creation'),
        ];

        /**
         * Steps
         * -- 1. Verify we have all required database credientials
         * -- 2. Verify connection to host
         * -- 3. Create the database (unless skipped)
         * --- 4. Download WordPress files
         * --- 5. Save WordPress files
         * --- 6. Download WordPress test files
         * --- 7. Save WordPress test files
         * --- 8. Change config data in wp-tests-config.php
         */

        try {
            $this->validateDatabaseCredentials();
            $this->connectToHost($input, $output);
            $this->createDatabase($input, $output);
            $this->downloadWordPressFiles($input, $output);
            $this->downloadWordPressTestFiles($input, $output);
            $this->configureWordPressTestFiles($input, $output);

            $output->writeln([
                '',
                \WPTS\CMD_ICONS['check'] . " Setup complete",
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

    /**
     * Check the db credentials array has all the necessary data
     */
    protected function validateDatabaseCredentials(): void
    {
        if ($this->db_creds['host'] == '') {
            throw new \InvalidArgumentException("Please provide a database host.");
        }

        if ($this->db_creds['socket'] && $this->db_creds['host'] == '') {
            throw new \InvalidArgumentException("Please provide a database host, alongside a unix socket.");
        }

        if ($this->db_creds['name'] == '') {
            throw new \InvalidArgumentException("Please provide a database name.");
        }

        if ($this->db_creds['user'] == '') {
            throw new \InvalidArgumentException("Please provide a database user.");
        }

        if ($this->db_creds['pass'] == '') {
            throw new \InvalidArgumentException("Please provide a database password.");
        }
    }

    /**
     * Connect to the database host
     * * Also connects to the database if 'skip-db-creation' is true
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     */
    protected function connectToHost(InputInterface $input, OutputInterface &$output): void
    {
        $output->writeln(\WPTS\CMD_ICONS['loading'] . ' Testing connection...');

        $db_string = "mysql:";

        if ($input->getOption('db-socket')) {
            $db_string .= "unix_socket={$this->db_creds['socket']};";
        } else {
            $db_string .= "host={$this->db_creds['host']};";
        }

        if ($input->getOption('skip-db-creation')) {
            $db_string .= "dbname={$this->db_creds['name']};";
        }

        $db_string .= "charset=UTF8";

        try {
            $db_connection = new \PDO(
                $db_string,
                $this->db_creds['user'],
                $this->db_creds['pass']
            );

            $db_connection->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);

            $this->db_connection = $db_connection;
        } catch (\PDOException $e) {
            \ray($e)->red();

            switch ($e->getCode()) {
                case '1045':
                    throw new \Exception("Couldn't connect to host. Is the username or password incorrect?");
                    break;
                default:
                    throw new \Exception("Couldn't connect to host. Please check the details.");
                    break;
            }
        }
    }

    /**
     * Create the database if 'skip-db-creation' option is false
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     */
    protected function createDatabase(InputInterface $input, OutputInterface &$output): void
    {
        if ($input->getOption('skip-db-creation')) {
            return;
        }

        $output->writeln(\WPTS\CMD_ICONS['loading'] . ' Creating database...');

        try {
            $this->db_connection->query("CREATE DATABASE IF NOT EXISTS {$this->db_creds['name']}");
        } catch (\Exception $e) {
            throw new \Exception("There was an error creating the database.");
        }
    }

    /**
     * Find latest version of WordPress and return download URL
     *
     * @return string
     */
    protected function getLatestWordPressDownloadUrl()
    {
        $version_check = $this->httpClient->get('https://api.wordpress.org/core/version-check/1.7/');

        $version_check_response = \json_decode($version_check->getBody()->getContents());

        if ($version_check->getStatusCode() != 200 || !\property_exists($version_check_response, 'offers')) {
            throw new \Exception('There was an error getting the latest version of WordPress. Please check your connection.'); // @codingStandardsIgnoreLine
        }

        return $version_check_response->offers[0]->download;
    }

    /**
     * Download and save latest version of WordPress
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     */
    protected function downloadWordPressFiles(InputInterface $input, OutputInterface &$output): void
    {
        //---- Download zip file
        $output->writeln(\WPTS\CMD_ICONS['loading'] . ' Downloading WordPress files...');

        // Remove old files
        try {
            $this->filesystem->deleteDirectory($this->wpDirectoryName);
        } catch (\Throwable $e) {
            throw new \Exception("Enable to delete old WordPress files.");
        }

        // Download
        $files_request = $this->httpClient->get($this->getLatestWordPressDownloadUrl());

        if ($files_request->getStatusCode() != 200) {
            throw new \Exception('There was an error downloading WordPress. Please check your connection.');
        }

        \file_put_contents($this->tmp_dir . '/' . $this->wpZipFilename, $files_request->getBody()->getContents());

        //---- Unzip files
        $output->writeln(\WPTS\CMD_ICONS['loading'] . ' Installing WordPress files...');

        if (!$this->filesystem->fileExists($this->wpZipFilename)) {
            throw new \InvalidArgumentException('No WordPress files found.');
        }

        $zip = new \ZipArchive();

        if ($zip->open($this->tmp_dir . '/' . $this->wpZipFilename) != true) {
            throw new \Exception('There was an error unzipping the WordPress files.');
        }

        $zip->extractTo($this->tmp_dir);
        $zip->close();

        try {
            // Remove unneccessary files
            $this->filesystem->delete($this->wpZipFilename);
        } catch (\Throwable $e) {
            throw new \Exception('There was an error installing the WordPress files.');
        }
    }

    /**
     * Download and save latest version of WordPress test files
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     */
    protected function downloadWordPressTestFiles(InputInterface $input, OutputInterface &$output): void
    {
        //---- Download zip file
        $output->writeln(\WPTS\CMD_ICONS['loading'] . ' Downloading WordPress test files...');

        // Remove old files
        try {
            $this->filesystem->deleteDirectory($this->wpTestsDirectoryName);
        } catch (\Throwable $e) {
            throw new \Exception("Enable to delete old WordPress test files.");
        }

        // Download
        $files_request = $this->httpClient->get(
            'https://github.com/WordPress/wordpress-develop/archive/refs/heads/master.zip'
        );

        if ($files_request->getStatusCode() != 200) {
            throw new \Exception('There was an error downloading the WordPress test files. Please check your connection.'); // @codingStandardsIgnoreLine
        }

        \file_put_contents($this->tmp_dir . '/' . $this->wpTestsZipFilename, $files_request->getBody()->getContents());

        //---- Unzip files
        $output->writeln(\WPTS\CMD_ICONS['loading'] . ' Installing WordPress test files...');

        if (!$this->filesystem->fileExists($this->wpTestsZipFilename)) {
            throw new \InvalidArgumentException('No WordPress test files found.');
        }

        $zip = new \ZipArchive();

        if ($zip->open($this->tmp_dir . '/' . $this->wpTestsZipFilename) != true) {
            throw new \Exception('There was an error unzipping the WordPress test files.');
        }

        $zip->extractTo($this->tmp_dir);
        $zip->close();

        try {
            // Move necessary files to WordPress test folder
            $this->filesystem->move(
                $this->wpTestsUnzippedFilename . '/src',
                $this->wpTestsDirectoryName . '/src'
            );

            $this->filesystem->move(
                $this->wpTestsUnzippedFilename . '/tests/phpunit/includes',
                $this->wpTestsDirectoryName . '/includes'
            );

            $this->filesystem->move(
                $this->wpTestsUnzippedFilename . '/tests/phpunit/data',
                $this->wpTestsDirectoryName . '/data'
            );

            $this->filesystem->move(
                $this->wpTestsUnzippedFilename . '/wp-tests-config-sample.php',
                $this->wpTestsDirectoryName . '/wp-tests-config.php'
            );

            // Remove unneccessary files
            $this->filesystem->delete($this->wpTestsZipFilename);
            $this->filesystem->deleteDirectory($this->wpTestsUnzippedFilename);
        } catch (\Throwable $e) {
            throw new \Exception('There was an error installing the WordPress test files.');
        }
    }

    /**
     * Change settings in wp-tests-config.php
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     */
    protected function configureWordPressTestFiles(InputInterface $input, OutputInterface &$output): void
    {
        $output->writeln(\WPTS\CMD_ICONS['loading'] . ' Configuring WordPress test files...');

        $transformer = new WPConfigTransformer(
            $this->tmp_dir . '/' . $this->wpTestsDirectoryName . '/' . 'wp-tests-config.php'
        );

        try {
            $transformer->update('constant', 'DB_NAME', $this->db_creds['name']);
            $transformer->update('constant', 'DB_USER', $this->db_creds['user']);
            $transformer->update('constant', 'DB_PASSWORD', $this->db_creds['pass']);
            $transformer->update('constant', 'DB_HOST', $this->db_creds['host']);
        } catch (\Throwable $e) {
            throw new \Exception("There was an error configuring the WordPress test files.");
        }
    }
}
