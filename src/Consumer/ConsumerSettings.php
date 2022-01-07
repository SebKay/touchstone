<?php

namespace WPTS\Consumer;

class ConsumerSettings
{
    protected string $testsDir            = '';
    protected string $unitTestsDir        = '';
    protected string $integrationTestsDir = '';

    protected array $plugins = [];

    public function __construct(string $root_path)
    {
        $file = $root_path . 'config.touchstone.php';

        if (!\file_exists($file)) {
            return;
        }

        $config = include $file;

        if (!\is_array($config)) {
            return;
        }

        $this->testsDir            = $root_path . ($config['directories']['all'] ?? '');
        $this->unitTestsDir        = $root_path . ($config['directories']['unit'] ?? '');
        $this->integrationTestsDir = $root_path . ($config['directories']['integration'] ?? '');

        $this->plugins = \array_map(function (array $data) {
            return new Plugin(
                $data['name'] ?? '',
                $data['file'] ?? '',
            );
        }, $config['plugins'] ?? []);
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

    /**
     * @return Plugin[]
     */
    public function plugins(): array
    {
        return $this->plugins;
    }
}
