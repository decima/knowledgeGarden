<?php

namespace App\Services\FileManager;

class NoMarkdownException extends \Exception
{
    public function __construct(public readonly string $realPath)
    {
        parent::__construct("file should not be handled as markdown");
    }
}