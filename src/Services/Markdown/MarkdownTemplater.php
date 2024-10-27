<?php

namespace App\Services\Markdown;

use Laminas\Dom\DOMXPath;
use League\CommonMark\Environment\Environment;
use League\CommonMark\Extension\CommonMark\CommonMarkCoreExtension;
use League\CommonMark\Extension\HeadingPermalink\HeadingPermalinkExtension;
use League\CommonMark\Extension\Table\TableExtension;
use League\CommonMark\Extension\TableOfContents\TableOfContentsExtension;
use League\CommonMark\MarkdownConverter;

class MarkdownTemplater
{
    public static function render($filename, $input, $parameters = []): Page
    {
        return (new static())($filename, $input, $parameters);
    }

    public function __invoke($filename, $input, $parameters = [])
    {


        $environment = new Environment();
        $environment->addExtension(new CommonMarkCoreExtension());
        $environment->addExtension(new HeadingPermalinkExtension());
        $environment->addExtension(new TableExtension());

        $converter = new MarkdownConverter($environment);

        $input = MarkdownPreprocessor::render($input, ["app_title" => "My awesome garden."]);
        $metadata = MarkdownMetadata::extract($input);
        $input = ($converter->convert($input)->getContent());

        return new Page($input, $this->makeTableOfContent($input), $metadata["title"] ?? $filename);
    }

    private function makeTableOfContent($input): array
    {
        $toc = [];
        $dom = new \DOMDocument();
        @$dom->loadHtml($input);
        $x = new DOMXPath($dom);
        $refDictionnary = [];
        foreach ($x->query('//h1|//h2|//h3|//h4|//h5|//h6') as $heading) {
            /** @var \DOMNode $heading */

            $level = intval(str_replace("h", "", $heading->nodeName));
            $parentCell = null;
            for ($i = count($refDictionnary) - 1; $i >= 0; $i--) {
                if ($refDictionnary[$i]["level"] < $level) {
                    $parentCell = $i;
                    break;
                }
            }
            $lastElement = count($refDictionnary);
            $refDictionnary[] = [
                "level" => $level,
                "content" => ltrim($heading->textContent, "Â¶"),
                "link" => $heading->childNodes?->item(0)?->attributes?->getNamedItem("href")?->textContent,
                "children" => [],
                "selected" => false,
            ];
            if ($parentCell !== null) {
                $refDictionnary[$parentCell]["children"][] = &$refDictionnary[$lastElement];
            } else {
                $toc[] = &$refDictionnary[$lastElement];
            }
        }
        return $toc;
    }

    private function DOMtitleInnerHTML(\DOMNode $element)
    {
        $innerHTML = "";
        $children = $element->childNodes;
        $skip = true;
        foreach ($children as $child) {
            if ($skip) {
                $skip = false;
                continue;
            }
            $innerHTML .= $element->ownerDocument->saveHTML($child);
        }

        return $innerHTML;
    }
}
