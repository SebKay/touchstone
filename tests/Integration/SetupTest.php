<?php

use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;
use WPTS\Console\Commands\Setup;

class SetupTest extends TestCase
{
    /**
     * @var CommandTester
     */
    protected $testTester;
    protected $cmd;

    public function setUp(): void
    {
        $app = new Application();

        $app->add(new Setup());

        $this->cmd        = $app->find('setup');
        $this->testTester = new CommandTester($this->cmd);
    }

    public function test_it_fails_when_no_database_host_is_provided()
    {
        $this->testTester->execute([
            'command'  => $this->cmd->getName(),
        ]);

        $this->assertSame(1, $this->testTester->getStatusCode());
        $this->assertStringContainsString('Please provide a database host.', $this->testTester->getDisplay());
    }

    public function test_it_fails_when_no_database_name_is_provided()
    {
        $this->testTester->execute([
            'command'   => $this->cmd->getName(),
            '--db-host' => 'test',
        ]);

        $this->assertSame(1, $this->testTester->getStatusCode());
        $this->assertStringContainsString('Please provide a database name.', $this->testTester->getDisplay());
    }

    public function test_it_fails_when_no_database_user_is_provided()
    {
        $this->testTester->execute([
            'command'   => $this->cmd->getName(),
            '--db-host' => 'test',
            '--db-name' => 'test',
        ]);

        $this->assertSame(1, $this->testTester->getStatusCode());
        $this->assertStringContainsString('Please provide a database user.', $this->testTester->getDisplay());
    }

    public function test_it_fails_when_no_database_password_is_provided()
    {
        $this->testTester->execute([
            'command'   => $this->cmd->getName(),
            '--db-host' => 'test',
            '--db-name' => 'test',
            '--db-user' => 'test',
        ]);

        $this->assertSame(1, $this->testTester->getStatusCode());
        $this->assertStringContainsString('Please provide a database password.', $this->testTester->getDisplay());
    }
}
