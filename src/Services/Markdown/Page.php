<?php

namespace App\Services\Markdown;

class Page
{
    public function __construct(
        public readonly string $content,
        public readonly array  $tableOfContent,
        public readonly string $title = "",
        public readonly array  $metadata = [],
    )
    {

    }

    public function toSlides(): array
    {
        $slidedContent = [];
        $content = $this->content;
        $slides = explode("<hr />", $content);
        foreach ($slides as $s) {
            $slidedContent[] = array_map(fn($k) => trim($k), explode("--", trim($s)));
        }
        return $slidedContent;
    }

}