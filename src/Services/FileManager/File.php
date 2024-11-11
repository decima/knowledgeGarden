<?php

namespace App\Services\FileManager;

class File
{

    public string $path;
    public string $filename = "";
    public string $content = "";
    public array|\stdClass $metadata;

    public function __construct()
    {
        $this->metadata = new \stdClass();
    }
}