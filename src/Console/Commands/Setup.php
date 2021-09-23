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

    protected $httpClient;
    protected $tmp_dir;
    protected $wpZipPath;
    protected $wpDevelopZipPath;
    protected $wpTestsDir;
    protected $db_creds = [];

    /**
     * @var \PDO
     */
    protected $db_connection;

    public function __construct()
    {
        parent::__construct();

        $this->httpClient       = new \GuzzleHttp\Client();
        $this->tmp_dir          = \sys_get_temp_dir();
        $this->wpZipPath        = $this->tmp_dir . '/wordpress.zip';
        $this->wpDevelopZipPath = $this->tmp_dir . '/wordpress-develop.zip';
        $this->wpTestsDir       = $this->tmp_dir . '/wordpress-tests-lib';
    }

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
            InputOption::VALUE_OPTIONAL,
            "Skip the creation of the database if it already exists",
            false
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $output->writeln(\WPTS_CMD_INTRO);

        $this->db_creds = [
            'host'          => $input->getOption('db-host') ?: '',
            'name'          => $input->getOption('db-name') ?: '',
            'user'          => $input->getOption('db-user') ?: '',
            'pass'          => $input->getOption('db-pass') ?: '',
            'skip_creation' => $input->getOption('skip-db-creation') ?: false,
        ];

        /**
         * Steps
         * -- 1. Verify we have all required database credientials
         * -- 2. Verify connection to host
         * -- 3. Create the database (unless skipped)
         * --- 4. Download WordPress files
         * --- 5. Save WordPress files
         * 6. Download WordPress test files
         * 7. Save WordPress test files
         */

        try {
            $this->verifyDatabaseCredentials();
            $this->connectToHost($input, $output);
            $this->createDatabase($input, $output);
            $this->downloadWordPressFiles($input, $output);
            $this->downloadWordPressTestFiles($input, $output);

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

    protected function verifyDatabaseCredentials()
    {
        if ($this->db_creds['host'] == '') {
            throw new \InvalidArgumentException("Please provide a database host.");
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

    protected function connectToHost(InputInterface $input, OutputInterface &$output)
    {
        $output->writeln(\WPTS_CMD_ICONS['loading'] . ' Testing connection...');

        $db_string = "mysql:host={$this->db_creds['host']};charset=UTF8";

        if ($input->getOption('skip-db-creation')) {
            $db_string .= ";dbname={$this->db_creds['name']}";
        }

        try {
            $db_connection = new \PDO(
                $db_string,
                $this->db_creds['user'],
                $this->db_creds['pass']
            );

            $db_connection->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);

            $this->db_connection = $db_connection;
        } catch (\PDOException $e) {
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

    protected function createDatabase(InputInterface $input, OutputInterface &$output)
    {
        if ($input->getOption('skip-db-creation')) {
            return;
        }

        $output->writeln(\WPTS_CMD_ICONS['loading'] . ' Creating database...');

        try {
            $this->db_connection->query("CREATE DATABASE {$this->db_creds['name']}");
        } catch (\Exception $e) {
            throw new \Exception("There was an error creating the database.");
        }
    }

    protected function getLatestWordPressDownloadUrl()
    {
        $version_check = $this->httpClient->get('https://api.wordpress.org/core/version-check/1.7/');

        $version_check_response = \json_decode($version_check->getBody()->getContents());

        if ($version_check->getStatusCode() != 200 || !\property_exists($version_check_response, 'offers')) {
            throw new \Exception('There was an error getting the latest version of WordPress. Please check your connection.');
        }

        return $version_check_response->offers[0]->download;
    }

    protected function downloadWordPressFiles(InputInterface $input, OutputInterface &$output)
    {
        //---- Download zip file
        $output->writeln(\WPTS_CMD_ICONS['loading'] . ' Downloading WordPress...');

        $files_request = $this->httpClient->get($this->getLatestWordPressDownloadUrl());

        if ($files_request->getStatusCode() != 200) {
            throw new \Exception('There was an error downloading WordPress. Please check your connection.');
        }

        \file_put_contents($this->wpZipPath, $files_request->getBody()->getContents());

        //---- Unzip files
        $output->writeln(\WPTS_CMD_ICONS['loading'] . ' Installing WordPress files...');

        if (!\file_exists($this->wpZipPath)) {
            throw new \InvalidArgumentException('No WordPress files found.');
        }

        $zip = new \ZipArchive();

        if ($zip->open($this->wpZipPath) != true) {
            throw new \Exception('There was an error unzipping the WordPress files.');
        }

        $zip->extractTo($this->tmp_dir);
        $zip->close();

        // Clean up unnecessary files and folders
        \unlink($this->tmp_dir . '/wordpress.zip');
    }

    protected function downloadWordPressTestFiles(InputInterface $input, OutputInterface &$output)
    {
        //---- Download zip file
        $output->writeln(\WPTS_CMD_ICONS['loading'] . ' Downloading WordPress test files...');

        // Save includes folders
        $files_request = $this->httpClient->get('https://github.com/WordPress/wordpress-develop/archive/refs/heads/master.zip');

        if ($files_request->getStatusCode() != 200) {
            throw new \Exception('There was an error downloading the WordPress test files. Please check your connection.');
        }

        \file_put_contents($this->wpDevelopZipPath, $files_request->getBody()->getContents());

        //---- Unzip files
        $output->writeln(\WPTS_CMD_ICONS['loading'] . ' Installing WordPress test files...');

        if (!\file_exists($this->wpDevelopZipPath)) {
            throw new \InvalidArgumentException('No WordPress test files found.');
        }

        $zip = new \ZipArchive();

        if ($zip->open($this->wpDevelopZipPath) != true) {
            throw new \Exception('There was an error unzipping the WordPress test files.');
        }

        $zip->extractTo($this->tmp_dir);
        $zip->close();

        $tmp_unzipped_dir = $this->tmp_dir . '/wordpress-develop-master';

        // Create WordPress test folder(s)
        if (!\file_exists($this->wpTestsDir)) {
            \mkdir($this->wpTestsDir);
        }

        if (!\file_exists($this->wpTestsDir . '/tests/phpunit/includes')) {
            \mkdir($this->wpTestsDir . '/tests/phpunit/includes', 0777, true);
        }

        if (!\file_exists($this->wpTestsDir . '/tests/phpunit/data')) {
            \mkdir($this->wpTestsDir . '/tests/phpunit/data', 0777, true);
        }

        // Move necessary files to WordPress test folder
        \rename($tmp_unzipped_dir . '/wp-tests-config-sample.php', $this->wpTestsDir . '/wp-tests-config-sample.php');
        \rename($tmp_unzipped_dir . '/tests/phpunit/includes', $this->wpTestsDir . '/tests/phpunit/includes');
        \rename($tmp_unzipped_dir . '/tests/phpunit/data', $this->wpTestsDir . '/tests/phpunit/data');

        // Clean up unnecessary files and folders
        \unlink($this->tmp_dir . '/wordpress-develop.zip');
    }
}
