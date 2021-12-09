<?php

namespace WPTS;

class UserConfiguration
{
    protected string $testDir            = '';
    protected string $unitTestDir        = '';
    protected string $integrationTestDir = '';

    public function __construct(array $configuration = [])
    {
        $this->testDir            = $configuration['directories']['all'] ?? '';
        $this->unitTestDir        = $configuration['directories']['unit'] ?? '';
        $this->integrationTestDir = $configuration['directories']['integration'] ?? '';
    }

    public function getTestsDirectory(): string
    {
        return $this->testDir;
    }

    public function getUnitTestsDirectory(): string
    {
        return $this->unitTestDir;
    }

    public function getIntegrationTestsDirectory(): string
    {
        return $this->integrationTestDir;
    }
}
