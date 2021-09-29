<?php

use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;
use WPTS\Console\Commands\Test;

class TestTest extends TestCase
{
    /**
     * @var CommandTester
     */
    protected $testTester;
    protected $cmd;

    public function setUp(): void
    {
        $app = new Application();

        $app->add(new Test());

        $this->cmd        = $app->find('test');
        $this->testTester = new CommandTester($this->cmd);
    }

    public function test_it_can_execute()
    {
        $this->testTester->execute([
            'command'  => $this->cmd->getName(),
            '--type'   => 'test',
        ]);

        $output = $this->testTester->getDisplay();

        $this->assertStringContainsString('Running test tests...', $output);
        $this->assertStringContainsString('Tests run successfully', $output);
    }
}
