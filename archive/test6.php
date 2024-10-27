<?php
require 'vendor/autoload.php';
ini_set("max_execution_time", 1);


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
    "[[a age=17]]i'm this old.[[/a]]" => "<a>i'm this old.</a>",
    "[[a age=17 name=Henri]]i'm [[_var kiwi /]] years old.[[/a]]" => "<a>i'm _MY_KIWI_VAR_ years old.</a>",
    "[[a age=17/]]" => "<a></a>",
    "[[_var kiwi/]]" => "_MY_KIWI_VAR_",
    "[[lorem/]]" => "lorem ipsum dolor sit amet",
    "[[cols]]
[[col 2]]col1[[/col]]
[[col]]col2[[/col]]
[[/cols]]" => "<div>
<div width='2'>col1</div>
<div>col2</div>
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
                <?php echo nl2br(htmlspecialchars($res = \App\Services\Markdown\MarkdownPreprocessor::render($k, ["kiwi" => "_MY_KIWI_VAR_"]))); ?>
            </td>
            <td class="status-<?= $res === $expected ? "success" : "failure" ?><?= $iter % 2; ?>"><?= $res === $expected ? "✅" : "❌"; ?></td>
        </tr>
        <?php $iter++; ?>

    <?php endforeach; ?>
</table>
