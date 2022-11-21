<?php

namespace WPTS\Consumer;

class ConsumerSettings
{
    protected string $testsDir            = '';
    protected string $unitTestsDir        = '';
    protected string $integrationTestsDir = '';

    protected array $plugins = [];
    protected Theme $theme;

    public function __construct(string $root_path)
    {
        $file   = $root_path . 'config.touchstone.php';
        $config = \file_exists($file) ? include $file : [];

        $this->testsDir            = $root_path . ($config['directories']['all'] ?? '');
        $this->unitTestsDir        = $root_path . ($config['directories']['unit'] ?? '');
        $this->integrationTestsDir = $root_path . ($config['directories']['integration'] ?? '');

        $this->plugins = \array_map(function (array $data) {
            return new Plugin(
                $data['name'] ?? '',
                $data['file'] ?? '',
            );
        }, $config['plugins'] ?? []);

        $theme_path = $config['theme']['root'] ?? '';

        $this->theme = new Theme($theme_path);
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

    public function theme(): Theme
    {
        return $this->theme;
    }

    public function bootstrapFile(): string
    {
        return $this->testsDir . '/bootstrap.php';
    }
}
