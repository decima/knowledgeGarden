<?php
require 'vendor/autoload.php';
ini_set("max_execution_time", 1);

class MarkdownImprover
{
    public const TAG_OPENER = "[[";
    public const TAG_CLOSER = "]]";

    private const REGEX_TAG_OPENER = '\[\[';
    private const REGEX_TAG_CLOSER = '\]\]';

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
        return $this->processTag($func, null, $args, $originalTag);
    }

    private function processTag($tag, ?string $inner, array $globalArgs = [], array $originalTag = [])
    {
        $args = array_filter(explode(" ", $tag));
        $func = array_shift($args);

        $k = 1;
        while (isset($argExploded["param$k"])) {
            $k++;
        }
        $bypassParam = true;
        foreach ($args as $t => $arg) {
            $argExploded = explode("=", $arg);
            if (count($argExploded) < 2) {
                dump($argExploded);
                $argExploded[1] = $argExploded[0];
                $argExploded[0] = "param$k";
                $k++;
            }
            $args[$argExploded[0]] = $argExploded[1];

        }
        if ($inner !== null) {
            $args["_slot"] = $inner;
        }
        dump($args);

        return $this->callTemplate($func, $args, $globalArgs, $originalTag);
    }

    private function callTemplate(string $templateName, array $args = [], array $parentArgs = [], array $originalTag = [])
    {
        $newArgs = [...$args, ...$parentArgs];
        $slotTag = static::TAG_OPENER . "_slot/" . static::TAG_CLOSER;
        $varTag = function ($name, $default = null) {
            return static::TAG_OPENER . "_var v=$name" . ($default !== null ? " default=$default" : "") . "/" . static::TAG_CLOSER;
        };
        try {
            $output = match ($templateName) {
                "_isset" => isset($newArgs[$newArgs["1"]]) ? $slotTag : "",
                "_slot" => $newArgs["_slot"],
                "_var" => $newArgs[$newArgs["v"]] ?? $newArgs[$newArgs["param1"]] ?? $newArgs["default"],
                "a" => "<a>" . $slotTag . "</a>",
                "b" => "<b>" . $slotTag . "</b>",
                "selfClosing" => "<sc/>",
                "lorem" => "lorem ipsum dolor sit amet",
                "cols" => "<div>$slotTag</div>",
                "col" => "<div width='{$varTag("param1",1)}'>$slotTag</div>",
                default => throw new Exception(),
            };
        } catch (Exception $exception) {
            return $originalTag[0] . ($newArgs["_slot"] ?? "") . $originalTag[1];
        }

        $content = self::render($output, $newArgs);
        return $content;

    }
}

$in = [
    "bonjour" => "bonjour",
    "[[a]][[/a]]" => "<a></a>",
    "[[a]]with content[[/a]]" => "<a>with content</a>",
    "[[a/]]" => "<a></a>",
    "[[a]]with [[selfClosing/]][[/a]]" => "<a>with <sc/></a>",
    "[[a]]with [[a/]][[/a]]" => "<a>with <a></a></a>",
    "[[a]]with [[a]]suba[[/a]][[/a]]" => "<a>with <a>suba</a></a>",
    "[[a]]with [[a]]suba[[/a]] and content[[/a]]" => "<a>with <a>suba</a> and content</a>",
    "[[a]]with [[b]][[/b]][[/a]]" => "<a>with <b></b></a>",
    "[[a]]with [[a]][[/a]][[/a]]" => "<a>with <a></a></a>",
    "[[a]][[a]][[a]][[/a]][[/a]][[/a]]" => "<a><a><a></a></a></a>",
    "[[a]][[a]][[a]][[/a]][[a]][[/a]][[/a]][[/a]]" => "<a><a><a></a><a></a></a></a>",
    "[[a/]][[a]][[a]][[/a]][[a/]][[/a]]" => "<a></a><a><a></a><a></a></a>",
    "[[a age=17]]i'm this old.[[/a]]" => "<a age=17>i'm this old.</a>",
    "[[a age=17 name=Henri]]i'm [[_var kiwi /]] years old.[[/a]]" => "<a age=17 name=Henri>i'm this old.</a>",
    "[[a age=17/]]" => "<a age=17></a>",
    "[[_var kiwi/]]" => "_MY_KIWI_VAR_",
    "[[lorem/]]" => "lorem ipsum dolor sit amet",
    "[[cols]]
[[col 2]]col1[[/col]]
[[col]]col2[[/col]]
[[/cols]]" => "<div>
<div width='2'>col1</div>
<div width='1'>col2</div>
</div>",
];
?>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/picnic">
<style>
    tr:has(td.status-failure0) {
        background: #f77;
    }

    tr:has(td.status-failure1) {
        background: #f99;
    }
</style>
<table style="width: 100%;">
    <tr>
        <th>input</th>
        <th>expected</th>
        <th>output</th>
        <th>result</th>
    </tr>
    <?php $iter = 0; ?>
    <?php foreach ($in as $k => $expected): ?>
        <?php $k = "begin $k end"; ?>
        <?php $expected = "begin $expected end"; ?>
        <tr>
            <td><?= $k ?></td>
            <td><?= htmlspecialchars($expected); ?></td>
            <td>
                <?php echo htmlspecialchars($res = MarkdownImprover::render($k, ["kiwi" => "_MY_KIWI_VAR_"])); ?>
            </td>
            <td class="status-<?= $res === $expected ? "success" : "failure" ?><?= $iter % 2; ?>"><?= $res === $expected ? "✅" : "❌"; ?></td>
        </tr>
        <?php $iter++; ?>

    <?php endforeach; ?>
</table>
