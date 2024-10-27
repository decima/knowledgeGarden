<?php

namespace App\Services\Markdown;

use Laminas\Dom\DOMXPath;
use League\CommonMark\Environment\Environment;
use League\CommonMark\Extension\CommonMark\CommonMarkCoreExtension;
use League\CommonMark\Extension\HeadingPermalink\HeadingPermalinkExtension;
use League\CommonMark\Extension\Table\TableExtension;
use League\CommonMark\Extension\TableOfContents\TableOfContentsExtension;
use League\CommonMark\MarkdownConverter;
use Symfony\Component\Yaml\Yaml;

class MarkdownMetadata
{
    public static function extract(&$input): array
    {
        $matches = [];
        if (!preg_match("/^---(?<metadata>.+)---/sixU", $input, $matches)) {
            return [];
        }
        $metadata = Yaml::parse($matches["metadata"]);
        $input = substr($input, strlen($matches[0]));
        return $metadata;

    }
}
