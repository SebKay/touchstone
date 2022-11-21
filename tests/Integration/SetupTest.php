<?php

use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;
use WPTS\Console\Commands\Setup;
use WPTS\Settings;

class SetupTest extends TestCase
{
    /**
     * @var CommandTester
     */
    protected $cmdTester;

    protected $cmd;

    public function setUp(): void
    {
        $app = new Application();

        $app->add(new Setup(new Settings()));

        $this->cmd       = $app->find('setup');
        $this->cmdTester = new CommandTester($this->cmd);
    }

    public function test_it_fails_when_no_database_host_is_provided()
    {
        $this->cmdTester->execute([
            'command'  => $this->cmd->getName(),
        ]);

        $this->assertSame(1, $this->cmdTester->getStatusCode());
        $this->assertStringContainsString('Please provide a database host.', $this->cmdTester->getDisplay());
    }

    public function test_it_fails_when_no_database_name_is_provided()
    {
        $this->cmdTester->execute([
            'command'   => $this->cmd->getName(),
            '--db-host' => 'test',
        ]);

        $this->assertSame(1, $this->cmdTester->getStatusCode());
        $this->assertStringContainsString('Please provide a database name.', $this->cmdTester->getDisplay());
    }

    public function test_it_fails_when_no_database_user_is_provided()
    {
        $this->cmdTester->execute([
            'command'   => $this->cmd->getName(),
            '--db-host' => 'test',
            '--db-name' => 'test',
        ]);

        $this->assertSame(1, $this->cmdTester->getStatusCode());
        $this->assertStringContainsString('Please provide a database user.', $this->cmdTester->getDisplay());
    }

    public function test_it_fails_when_no_database_password_is_provided()
    {
        $this->cmdTester->execute([
            'command'   => $this->cmd->getName(),
            '--db-host' => 'test',
            '--db-name' => 'test',
            '--db-user' => 'test',
        ]);

        $this->assertSame(1, $this->cmdTester->getStatusCode());
        $this->assertStringContainsString('Please provide a database password.', $this->cmdTester->getDisplay());
    }
}
