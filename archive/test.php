<?php
require 'vendor/autoload.php';

$input = <<<TXT

<T:alink|"https://perdu.com/>

<T:alink|https://perdu.com>Perdu.com</T:alink>
<T:bold|123>hello</T:bold>

<T:cols>
    <T:col>hello</T:col>
    <T:col>world</T:col>

</T:cols>

<T:alink>OJH</T:blink>

TXT;

function cols($slot)
{
    return "<COLUMNS>$slot</COLUMNS>";
}

function col($slot)
{
    return "<COL>$slot</COL>";
}

function bold($slot, string|int $min = 20)
{
    if (is_string($min)) {
        $min = intval($min);
    }
    $min = $min / 10;
    return str_repeat("*", $min) . "test.php" . str_repeat("*", $min);
}

function lorem()
{
    return "lorem ipsum dolor sit amet";
}

function alink($slot, $url = "")
{
    if (strlen($url) == 0) {
        $url = $slot;
    }
    return "<a href='$url'>$slot</a>";
}

$keyword = "T";
const RE1 = <<<REGEXP
\<T:(?<fun>(?:(?!>|\|).)+)(?<args>(?:\|(?:(?!\/>).)+)*)\/>
REGEXP;

const RE2 = <<<REGEXP
\<T:(?<fun>(?:(?!-?/>|>|\|).)+)(?<args>(?:\|(?:(?!-?/>|>|\|).)+)*)>(?<slot>(?:(?!</T:\g<fun>>).)+)</T:\g<fun>\>
REGEXP;

$output = $input;


$output = preg_replace_callback("@" . RE2 . "@Juis", function ($match) {
    $fun = $match["fun"];
    $args = explode("|", ltrim($match["args"] ?? "", "|"));
    array_unshift($args, $match["slot"]);
    return call_user_func_array($fun, $args);
}, $output);

$output = preg_replace_callback("@" . RE1 . "@Juis", function ($match) {
    $fun = $match["fun"];
    $args = explode("|", ltrim($match["args"] ?? "", "|"));
    return call_user_func_array($fun, $args);
}, $output);


echo $output;
die();



