<?php

namespace App\Services\Markdown;


use Exception;
use Symfony\Component\ExpressionLanguage\ExpressionFunction;
use Symfony\Component\ExpressionLanguage\ExpressionLanguage;

class MarkdownPreprocessor
{
    public const TAG_OPENER = "[[";
    public const TAG_CLOSER = "]]";

    private const REGEX_TAG_OPENER = '\[\[';
    private const REGEX_TAG_CLOSER = '\]\]';
    private ExpressionLanguage $expressionLanguage;

    private function __construct()
    {
        $this->expressionLanguage = new ExpressionLanguage();
    }

    public static function render($input, array $args = [])
    {
        return (new self())($input, $args);
    }

    public function __invoke($input, array $args = [])
    {
        return $this->parser($input, $offsetToSkip, $args);
    }

    private function parser($input, &$offsetToSkip = 0, array $args = [])
    {
        $position = 0;

        $regExTagOpeningNonClosing = "#(?<tag>" . static::REGEX_TAG_OPENER . "(?<func>[^\/]*?)(?<isSelfClosing>\/)?" . static::REGEX_TAG_CLOSER . ")#";
        $regExTagClosingSpecial = "#(?<tag>" . static::REGEX_TAG_OPENER . "\/(?<endfunc>.*?)" . static::REGEX_TAG_CLOSER . ")#";
        $results = "";
        while ($position < strlen($input)) {
            $hasOpenTag = preg_match($regExTagOpeningNonClosing, $input, $openMatch, PREG_OFFSET_CAPTURE, $position);
            $hasCloseTag = preg_match($regExTagClosingSpecial, $input, $closeMatch, PREG_OFFSET_CAPTURE, $position);
            $closePosition = strlen($input);
            $openPosition = strlen($input);
            $openTagLength = 0;
            $openFunction = "";
            $isSelfClose = false;
            $openTag = "";
            $closeTag = "";

            if ($hasCloseTag) {
                $closePosition = $closeMatch["tag"][1];
                $closeTag = $closeMatch["tag"][0];
            }
            if ($hasOpenTag) {
                $openPosition = $openMatch["tag"][1];
                $openTagLength = strlen($openMatch["tag"][0]);
                $openTag = $openMatch["tag"][0];
                $openFunction = $openMatch["func"][0];
                $isSelfClose = isset($openMatch["isSelfClosing"]);
            }
            if (min($openPosition, $closePosition) - $position > 0) {
                $results .= substr($input, $position, min($openPosition, $closePosition) - $position);
                $position = min($openPosition, $closePosition);
                continue;
            }
            if ($closePosition <= $openPosition) {
                $offsetToSkip = $closeMatch["tag"][1] + strlen($closeMatch["tag"][0]);
                break;
            } else {
                $newOffset = $openPosition + $openTagLength;
                if ($isSelfClose) {
                    $results .= $this->processSelfClosingTag($openFunction, $args, [$openTag, $closeTag]);
                    $position = $newOffset;
                    continue;
                }
                $toSkip = 0;
                $inner = $this->parser(substr($input, $newOffset), $toSkip, $args);
                $results .= $this->processTag($openFunction, $inner, $args, [$openTag, $closeTag]);
                $position = $newOffset + $toSkip;
            }
        }
        return $results;
    }


    private function processSelfClosingTag($func, array $args = [], array $originalTag = [])
    {
        return $this->processTag($func, null, $args, [$originalTag[0]]);
    }

    private function processTag($tag, ?string $inner, array $globalArgs = [], array $originalTag = [])
    {
        preg_match_all('@([^\s"]+|"([^"]*)")+@', $tag, $matches);
        $args = $matches[0];
        $func = array_shift($args);

        $k = 1;
        foreach ($args as $t => $arg) {
            $argExploded = explode("=", $arg);
            if (count($argExploded) < 2) {
                if ($tag !== "_var") {
                    $argExploded[1] = $argExploded[0];
                    $argExploded[0] = "param$k";
                }
                $k++;
            }
            $args[$argExploded[0]] = trim($argExploded[1], '"');

        }
        if ($inner !== null) {
            $args["_slot"] = $inner;
        }

        return $this->callTemplate($func, $args, $globalArgs, $originalTag);
    }

    private function callTemplate(string $templateName, array $args = [], array $parentArgs = [], array $originalTag = [])
    {
        $newArgs = [...$parentArgs, ...$args];
        $slotTag = static::TAG_OPENER . "_slot/" . static::TAG_CLOSER;

        $tryEvaluate = function ($condition, $args, $default = null) {
            try {
                return $this->expressionLanguage->evaluate($condition, $args);
            } catch (Exception) {
            }
            return $default;

        };

        if (file_exists(__DIR__ . "/builtins/$templateName")) {
            return self::render(file_get_contents(__DIR__ . "/builtins/$templateName"), $newArgs);
        }
        try {
            $output = match ($templateName) {
                "_if" => $tryEvaluate($newArgs["param1"], $newArgs, false) ? $slotTag : "",
                "_isset" => isset($newArgs[$newArgs["v"]]) ? $slotTag : "",
                "_slot" => $newArgs["_slot"] ?? "",
                "_var" => $newArgs[$newArgs["v"] ?? $newArgs["_slot"]] ?? $newArgs["default"] ?? "",
                default => throw new UnprocessableTagException(),
            };
        } catch (UnprocessableTagException $exception) {
            return $originalTag[0] . (isset($originalTag[1]) ? $newArgs["_slot"] . $originalTag[1] : "");
        }

        $content = self::render($output, $newArgs);
        return $content;

    }
}