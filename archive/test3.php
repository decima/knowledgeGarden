<?php
require 'vendor/autoload.php';
function parser($input, $position = 0)
{
    $results = "";
    while ($position < strlen($input)) {

        if (!preg_match('#\[\[([^/]*?)(\/?)\]\]#m', $input, $matches, PREG_OFFSET_CAPTURE, $position)) {
            $end = substr($input, $position);
            if (preg_match('#\[\[/.*\]\]$#', $end, $endMatch, PREG_OFFSET_CAPTURE)) {
                $end = substr($end, 0, $endMatch[0][1]);
            }
            return $results . $end;
        }
        $tag = $matches[0][0];
        $tagOffset = $matches[0][1];
        $results .= substr($input, $position, $tagOffset - $position);
        $tagContent = $matches[1][0];
        $funcArgs = array_values(array_filter(explode(" ", $tagContent)));
        $funcName = array_shift($funcArgs);
        $isSelfClosing = $matches[2][0] === "/";


        $position = strlen($tag) + $tagOffset;
        if ($isSelfClosing) {
            $results .= resolveSelfClosingTag($funcName, $funcArgs);
            continue;
        }
        if (preg_match('#\[\[\/(' . $funcName . ')\]\]#m', $input, $matchesNextClosing, PREG_OFFSET_CAPTURE, $position)) {
            $nextClosingOffset = $matchesNextClosing[0][1];
            $nextClosingTag = $matchesNextClosing[0][0];
            $nextPosition = $nextClosingOffset + strlen($nextClosingTag);
            $substr = substr($input, $position, $nextPosition - $position);
            $results .= resolveTag($funcName, $funcArgs, $substr);
            $position = $nextPosition;
        }
    }
    return $results;
}

function resolveSelfClosingTag(string $funcName, array $funcArgs)
{
    return resolveTag($funcName, $funcArgs, "");
}

function resolveTag(string $funcName, array $funcArgs, string $inner)
{
    $output = "<$funcName(" . implode("|", $funcArgs) . ")>";

    $output .= parser($inner);
    $output .= "</$funcName>";
    return $output;
}

// Exemple d'utilisation
$texte = "[[coucou/]] haha
non
[[a]]bonjour a[[/a]]
[[b]] no [[/b]]

[[cols]]
    [[col]]
        COL_1
    [[/col]]
    [[col]]
        COL_2
    [[/col]]
[[/cols]]
[[c wow/]]";

$resultat = parser($texte);
dump($resultat);

