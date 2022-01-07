<?php

namespace WPTS\Consumer;

class Theme
{
    protected string $directoryPath = '';
    protected string $directoryName = '';

    public function __construct(string $directory_path)
    {
        $this->directoryPath = $directory_path;
        $this->directoryName = \basename($this->directoryPath);
    }

    public function directoryPath(): string
    {
        return $this->directoryPath;
    }

    public function directoryName(): string
    {
        return $this->directoryName;
    }
}
