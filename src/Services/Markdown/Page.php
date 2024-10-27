<?php

namespace App\Services\Markdown;

class Page
{
    public function __construct(
        public readonly string $content,
        public readonly array  $tableOfContent,
        public readonly string $title = "",
    )
    {

    }

}