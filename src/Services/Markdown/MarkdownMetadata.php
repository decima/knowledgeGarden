<?php

namespace App\Services\Markdown;

use Laminas\Dom\DOMXPath;
use League\CommonMark\Environment\Environment;
use League\CommonMark\Extension\CommonMark\CommonMarkCoreExtension;
use League\CommonMark\Extension\HeadingPermalink\HeadingPermalinkExtension;
use League\CommonMark\Extension\Table\TableExtension;
use League\CommonMark\Extension\TableOfContents\TableOfContentsExtension;
use League\CommonMark\MarkdownConverter;
use stringEncode\Exception;
use Symfony\Component\Yaml\Yaml;
use Yosymfony\Toml\Toml;

class MarkdownMetadata
{
    public static function extract(&$input): array
    {
        $matches = [];
        if (!preg_match("/^\s?(:?---|\+\+\+)(?<metadata>.+)(:?---|\+\+\+)/sixU", $input, $matches)) {
            return [];
        }
        $metadata = static::parseYaml($matches["metadata"]);
        $input = substr($input, strlen($matches[0]));
        return $metadata;

    }

    public static function extractFromFileInfo(\SplFileInfo $fileInfo): array
    {
        $fileObject = $fileInfo->openFile('r');

        do {
            $startingRow = trim($fileObject->current());
            $fileObject->next();
        } while ($startingRow === '' && !$fileObject->eof());
        if ($startingRow !== "---" && $startingRow !== "+++") {
            return [];
        }
        $content = "";
        $ended = false;
        while (!$fileObject->eof()) {
            $row = $fileObject->fgets();
            if (trim($row) === "---" || trim($row) === "+++") {
                $ended = true;
                break;
            }
            $content .= $row;
        }
        if (!$ended) {
            return [];
        }

        return static::parseYaml($content);
    }

    private static function parseYaml($rawMetadata): array
    {
        try {
            $u = Yaml::parse($rawMetadata);
            if (is_string($u)) {
                throw new \Exception();
            }
            return $u;
        } catch (\Exception) {
            $df = self::parseToml($rawMetadata);
            return $df;
        }
        return [];
    }

    private static function parseToml($rawMetadata): array
    {
        return Toml::parse($rawMetadata);
    }
}
