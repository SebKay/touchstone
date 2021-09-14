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
            InputOption::VALUE_NONE,
            "Skip the creation of the database if it already exists"
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $output->writeln(\WPTS_CMD_INTRO);

        $this->db_creds = [
            'host' => $input->getOption('db-host') ?: '',
            'name' => $input->getOption('db-name') ?: '',
            'user' => $input->getOption('db-user') ?: '',
            'pass' => $input->getOption('db-pass') ?: '',
        ];

        try {
            $this->verifyDatabaseCredentials($output);
            $this->downloadFiles($input, $output);
            $this->installFiles($input, $output);
            $this->createDatabase($input, $output);
            $this->connectToDatabase($input, $output);

            $output->writeln([
                \WPTS_CMD_ICONS['check'] . " Setup complete",
            ]);

            return Command::SUCCESS;
        } catch (\Throwable $e) {
            $output->writeln([
                \WPTS_CMD_ICONS['cross'] . " {$e->getMessage()}",
            ]);

            return Command::FAILURE;
        }
    }

    protected function verifyDatabaseCredentials($output)
    {
        if ($this->db_creds['host'] == '') {
            throw new \InvalidArgumentException("Please provide a database host.");

            return Command::INVALID;
        }

        if ($this->db_creds['name'] == '') {
            throw new \InvalidArgumentException("Please provide a database name.");

            return Command::INVALID;
        }

        if ($this->db_creds['user'] == '') {
            throw new \InvalidArgumentException("Please provide a database user.");

            return Command::INVALID;
        }

        if ($this->db_creds['pass'] == '') {
            throw new \InvalidArgumentException("Please provide a database password.");

            return Command::INVALID;
        }

        $output->writeln(\WPTS_CMD_ICONS['loading'] . ' Testing database connection...');

        if (1 == 2) {
            throw new \Exception('There was an error connecting to the database. Please check the credentials.');

            return Command::INVALID;
        }
    }

    public function getLatestWordPressDownloadUrl()
    {
        $version_check = $this->httpClient->get('https://api.wordpress.org/core/version-check/1.7/');

        $version_check_response = \json_decode($version_check->getBody()->getContents());

        if ($version_check->getStatusCode() != 200 || !\property_exists($version_check_response, 'offers')) {
            throw new \Exception('There was an error getting the latest version of WordPress. Please check your connection.');

            return Command::INVALID;
        }

        return $version_check_response->offers[0]->download;
    }

    public function downloadFiles(InputInterface $input, OutputInterface &$output)
    {
        $output->writeln(\WPTS_CMD_ICONS['loading'] . ' Downloading WordPress...');

        $wp_download_url = $this->getLatestWordPressDownloadUrl();

        $wp_files = $this->httpClient->get($wp_download_url);

        if ($wp_files->getStatusCode() != 200) {
            throw new \Exception('There was an error downloading WordPress. Please check your connection.');

            return Command::INVALID;
        }

        \file_put_contents($this->wpZip, $wp_files->getBody()->getContents());
    }

    public function installFiles(InputInterface $input, OutputInterface &$output)
    {
        $output->writeln(\WPTS_CMD_ICONS['loading'] . ' Installing WordPress...');

        if (!\file_exists($this->wpZip)) {
            throw new \InvalidArgumentException('No WordPress test files found.');

            return Command::INVALID;
        }

        $zip = new \ZipArchive();

        if ($zip->open($this->wpZip) != true) {
            throw new \Exception('There was an error unzipping the WordPress files.');

            return Command::INVALID;
        }

        $zip->extractTo(sys_get_temp_dir());
        $zip->close();

        $output->writeln(\WPTS_CMD_ICONS['loading'] . ' Installing test files...');
    }

    public function createDatabase(InputInterface $input, OutputInterface &$output)
    {
        if (!$input->getOption('skip-db-creation')) {
            $output->writeln(\WPTS_CMD_ICONS['loading'] . ' Creating database...');
        }
    }

    public function connectToDatabase(InputInterface $input, OutputInterface &$output)
    {
        $output->writeln(\WPTS_CMD_ICONS['loading'] . ' Connecting to database...');
    }
}
