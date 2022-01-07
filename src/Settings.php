<?php

namespace WPTS;

use WPTS\Consumer\ConsumerSettings;

class Settings
{
    protected string $appRootPath           = '';
    protected string $consumerRootPath      = '';
    protected string $tempDirectory         = '';
    protected string $wpTestFilesDirectory  = '';
    protected string $phpunitConfigPath     = '';
    protected string $phpunitExecutablePath = '';

    protected ConsumerSettings $consumerSettings;

    protected string $testsDir            = '';
    protected string $unitTestsDir        = '';
    protected string $integrationTestsDir = '';

    public function __construct()
    {
        $this->appRootPath           = __DIR__ . '/../';
        $this->consumerRootPath      = \exec('pwd') . '/';
        $this->tempDirectory         = \sys_get_temp_dir() . '/';
        $this->wpTestFilesDirectory  = $this->tempDirectory() . 'wordpress-tests-lib';
        $this->phpunitConfigPath     = $this->appRootPath() . 'phpunit-touchstone.xml';
        $this->phpunitExecutablePath = $this->consumerRootPath() . 'vendor/bin/phpunit';

        $this->consumerSettings = new ConsumerSettings($this->consumerRootPath());

        $this->testsDir            = $this->consumerSettings()->testsDirectory() ?: $this->consumerRootPath()  . 'tests';
        $this->unitTestsDir        = $this->consumerSettings()->unitTestsDirectory() ?: $this->consumerRootPath()  . 'tests/Unit';
        $this->integrationTestsDir = $this->consumerSettings()->integrationTestsDirectory() ?: $this->consumerRootPath()  . 'tests/Integration';
    }

    /**
     * Path to the Touchstone app root (with trailing '/')
     */
    public function appRootPath(): string
    {
        return $this->appRootPath;
    }

    /**
     * Path to consumer project root (with trailing '/')
     */
    public function consumerRootPath(): string
    {
        return $this->consumerRootPath;
    }

    public function tempDirectory(): string
    {
        return $this->tempDirectory;
    }

    public function wpTestFilesDirectory(): string
    {
        return $this->wpTestFilesDirectory;
    }

    public function phpunitConfigPath(): string
    {
        return $this->phpunitConfigPath;
    }

    public function phpunitExecutablePath(): string
    {
        return $this->phpunitExecutablePath;
    }

    public function consumerSettings(): ConsumerSettings
    {
        return $this->consumerSettings;
    }

    public function testsDirectory(): string
    {
        return $this->testsDir;
    }

    public function unitTestsDirectory(): string
    {
        return $this->unitTestsDir;
    }

    public function integrationTestsDirectory(): string
    {
        return $this->integrationTestsDir;
    }
}
