<?php
require 'vendor/autoload.php';
ini_set("max_execution_time", 1);
function parser($input, &$offsetToSkip = 0, $level = 0)
{
    dump("parsing \"$input\"");

    $tagOpener = "\[";
    $tagCloser = "\]";
    $regExTagOpeningNonClosing = "#(?<tag>$tagOpener(?<func>[^\/]*?)(?<isSelfClosing>\/)?$tagCloser)#";
    $regExTagClosingSpecial = "#(?<tag>$tagOpener\/(?<endfunc>.*?)$tagCloser)#";
    $results = "";
    while ($position < strlen($input)) {
        $hasOpenTag = preg_match($regExTagOpeningNonClosing, $input, $openMatch, PREG_OFFSET_CAPTURE, $position);
        $hasCloseTag = preg_match($regExTagClosingSpecial, $input, $closeMatch, PREG_OFFSET_CAPTURE, $position);
        $endTag = strlen($input);
        if ($hasCloseTag) {
            $endTag = $closeMatch["tag"][1];
        }

        $tag = $openMatch["tag"][0] ?? "";
        $startTag = $openMatch["tag"][1] ?? strlen($input);

        if (($m = min($startTag, $endTag)) > 0) {
            $results .= substr($input, 0, $m);
            $position = $m + strlen($tag);
            continue;

        }

        $functionName = $openMatch["func"][0];
        $position = $startTag + strlen($tag);
        if (isset($openMatch["isSelfClosing"])) {
            dump("closing tag");
            $results .= processSelfClosingTag($functionName);
            continue;
        }

        $newOffsetToSkip = 0;
        $results .= processTag($functionName, parser($input, $newOffsetToSkip, $level++));
        $position += $newOffsetToSkip;
    }
    return $results;
}

function processSelfClosingTag($func)
{
    return processTag($func, "");
}

function processTag($func, $inner)
{
    return "<$func>$inner</$func>";
}

function wrapWithDepth($content, $depth)
{
    return $content;
}

$in = [
    "bonjour" => "bonjour",
    "[a][/a]" => "<a></a>",
    "[a]with content[/a]" => "<a>with content</a>",
    "[a/]" => "<a></a>",
    "[a]with [selfClosing/][/a]" => "<a>with <selfClosing></selfClosing></a>",
    "[a]with [a/][/a]" => "<a>with <a></a></a>",
    "[a]with [a]suba[/a][/a]" => "<a>with <a>suba</a></a>",
    "[a]with [a]suba[/a] and content[/a]" => "<a>with <a>suba</a> and content</a>",
    "[a]with [b][/b][/a]" => "<a>with <b></b></a>",
    "[a]with [a][/a][/a]" => "<a>with <a></a></a>",
    "[a][a][a][/a][/a][/a]" => "<a><a><a></a></a></a>",
    "[a][a][a][/a][a][/a][/a][/a]" => "<a><a><a></a><a></a></a></a>",
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
                <?php echo htmlspecialchars($res = parser($k)); ?>
            </td>
            <td class="status-<?= $res === $expected ? "success" : "failure" ?><?= $iter % 2; ?>"><?= $res === $expected ? "✅" : "❌"; ?></td>
        </tr>
        <?php $iter++; ?>

    <?php endforeach; ?>
</table>
