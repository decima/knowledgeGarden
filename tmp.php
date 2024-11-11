<?php
$availableTags = ["demo", "test", "cookie", "structure", "donnée", "bdd"];
@delTree("./data/z-fake");
$parentFolder = ["./data/z-fake"];
@mkdir("./data/z-fake");
$dirId = 0;
for ($i = 0; $i < 1000; $i++) {
    $dir = $parentFolder[rand(0, count($parentFolder) - 1)];
    $shouldCreateNewFolder = rand(0, 100) % 10 === 0;
    if ($shouldCreateNewFolder) {
        $dir = $dir . "/dir_" . $dirId;
        @mkdir($dir);
        $dirId++;
        $parentFolder[] = $dir;
    }
    $j = rand(0, count($availableTags) - 1);
    shuffle($availableTags);
    $tags = implode(", ", array_slice($availableTags, 0, $j));
    $content = <<<MD
---
title: file $i
tags: [$tags]
---
# This is the default content of file $i

[[lorem size=5/]]
MD;

    file_put_contents("$dir/$i.md", $content);


}


function delTree($dir)
{

    $files = array_diff(scandir($dir), array('.', '..'));

    foreach ($files as $file) {

        (is_dir("$dir/$file")) ? delTree("$dir/$file") : unlink("$dir/$file");

    }

    return rmdir($dir);

}