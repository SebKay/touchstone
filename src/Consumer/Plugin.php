<?php

namespace WPTS\Consumer;

class Plugin
{
    protected string $name     = '';
    protected string $filePath = '';

    public function __construct(string $name, string $file_path)
    {
        $this->name     = $name;
        $this->filePath = $file_path;
    }

    public function name(): string
    {
        return $this->name;
    }

    public function filePath(): string
    {
        return $this->filePath;
    }
}
