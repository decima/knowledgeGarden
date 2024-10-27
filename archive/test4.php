<?php
require 'vendor/autoload.php';
ini_set("max_execution_time", 5);
function parser($input, &$position = 0, $depth = 0, $currentTag = null)
{
    dump("parsing \"$input\"");

    $tagOpener = "\[";
    $tagCloser = "\]";
    $regExTagOpeningNonClosing = "#(?<tag>$tagOpener(?<func>[^\/]*?)(?<isSelfClosing>\/)?$tagCloser)#";
    $regExTagClosingSpecial = "#$tagOpener\/(.*?)$tagCloser#";
    $results = "";
    while ($position < strlen($input)) {
        $hasEnd = preg_match($regExTagClosingSpecial, $input, $endMatches, PREG_OFFSET_CAPTURE, $position);
        $end = 0;
        if ($hasEnd) {
            $end = $endMatches[0][1];
        }
        $begin = strlen($input) - 1;

        $isMatching = preg_match($regExTagOpeningNonClosing, $input, $matches, flags: PREG_OFFSET_CAPTURE, offset: $position);
        if (!$isMatching) {
            dump("no starting tag found, closing tab");
            // add detecting that the tag is closing
            $end = substr($input, $position);
            if (preg_match($regExTagClosingSpecial, $input, $endMatches, PREG_OFFSET_CAPTURE, $position)) {
                dump("removing end tag");

                $end = substr($input, 0, $endMatches[0][1] - $position);
            }

            $results .= $end;
            break;
        }

        $func = $matches["func"][0];
        dump("found starting tag " . $func);
        $isSelfClosing = isset($matches["isSelfClosing"]);
        $tag = $matches["tag"][0];
        $tagOffset = (int)$matches["tag"][1];

        if ($hasEnd && $end > $matches[0][1]) {
            dump("END TAG FOUND, CLOSING, with currentTag $func");
            $position = $endMatches[0][1] + strlen($endMatches[0][0]);
            return processTag($currentTag, parser(substr($input, 0, $endMatches[0][1]), depth: $depth, currentTag: $func));
        }


        $beforeTagContent = substr($input, offset: $position, length: $tagOffset - $position);
        $results .= $beforeTagContent;
        $position = strlen($tag) + $tagOffset;
        if ($isSelfClosing) {
            dump("IS SELF CLOSING");
            $results .= processSelfClosingTag($func);
            continue;
        }


        $nextStr = substr($input, $position);
        $newOffset = 0;
        $results .= parser($nextStr, $newOffset, $depth + 1, $func);
        $position = strlen($tag) + $tagOffset + $newOffset;
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
    "[a/]" => "<a></a>",
    "[a][/a]" => "<a></a>",
    "[a]with content[/a]" => "<a>with content</a>",
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
