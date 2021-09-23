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
    protected $wpZip;
    protected $db_creds = [];

    /**
     * @var \PDO
     */
    protected $db_connection;

    public function __construct()
    {
        parent::__construct();

        $this->httpClient = new \GuzzleHttp\Client();
        $this->wpZip      = \sys_get_temp_dir() . '/wordpress.zip';
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
         * 1. Verify we have all required database credientials
         * 2. Verify connection to host
         * 3. Create the database (unless skipped)
         * 4. Download WordPress files
         * 5. Save WordPress files
         * 6. Download WordPress test files
         * 7. Save WordPress test files
         */

        try {
            $this->verifyDatabaseCredentials($output);
            $this->connectToHost($output);
            $this->createDatabase($input, $output);
            $this->downloadWordPressFiles($input, $output);

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

    protected function verifyDatabaseCredentials($output)
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

    protected function connectToHost($output)
    {
        $output->writeln(\WPTS_CMD_ICONS['loading'] . ' Testing connection...');

        try {
            $db_connection = new \PDO(
                "mysql:host={$this->db_creds['host']};charset=UTF8",
                $this->db_creds['user'],
                $this->db_creds['pass']
            );

            $db_connection->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);

            $this->db_connection = $db_connection;
        } catch (\PDOException $e) {
            throw new \Exception('There was an error connecting to the database. Please check the credentials.');
        }
    }

    protected function createDatabase(InputInterface $input, OutputInterface &$output)
    {
        if ($input->getOption('skip-db-creation')) {
            return;
        }

        $output->writeln(\WPTS_CMD_ICONS['loading'] . ' Creating database...');

        try {
            $this->db_connection->query("CREATE DATABASE IF NOT EXISTS {$this->db_creds['name']}");
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
        //---- Download files
        $output->writeln(\WPTS_CMD_ICONS['loading'] . ' Downloading WordPress...');

        $wp_download_url = $this->getLatestWordPressDownloadUrl();

        $wp_files = $this->httpClient->get($wp_download_url);

        if ($wp_files->getStatusCode() != 200) {
            throw new \Exception('There was an error downloading WordPress. Please check your connection.');
        }

        \file_put_contents($this->wpZip, $wp_files->getBody()->getContents());

        //---- Save files
        if (!\file_exists($this->wpZip)) {
            throw new \InvalidArgumentException('No WordPress test files found.');
        }

        $zip = new \ZipArchive();

        if ($zip->open($this->wpZip) != true) {
            throw new \Exception('There was an error unzipping the WordPress files.');
        }

        $zip->extractTo(sys_get_temp_dir());
        $zip->close();
    }
}
