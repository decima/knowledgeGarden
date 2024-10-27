<?php

use League\CommonMark\Environment\Environment;
use League\CommonMark\Event\DocumentPreParsedEvent;
use League\CommonMark\Extension\CommonMark\CommonMarkCoreExtension;
use League\CommonMark\Extension\HeadingPermalink\HeadingPermalinkExtension;
use League\CommonMark\Extension\Table\TableExtension;
use League\CommonMark\Extension\TableOfContents\TableOfContentsExtension;
use League\CommonMark\MarkdownConverter;
use Symfony\Component\DomCrawler\Crawler as Crawler;

require 'vendor/autoload.php';

$input = <<<TXT

# <v:self v:slot="app_title" v:default="Default site title."/>
<v:h1 v:slot="app_title" v:default="Default site title."/>
<v:div v:width="width|1" height="100px" v:default="oh"></v:div>
    <v:self v:slot="app_title" v:default="ENFER."/>
<div>

## ha oui ?

<h1 style="color:red;">Hello &amp;lt; bonne nuit</h1>
</div>
<t:bold>C'est du gras.</t:bold>
<t:lorem/>
<t:helloname name="Henri"/>
<t:cols>
    <t:col width="3">abc</t:col>
    <t:col>def</t:col>
</t:cols>

<t:lorem/>

```
<div>hello</div>
```
TXT;


function cols()
{
    return "<div style='display: flex;'><v:self v:slot='slot'/></div>";
}

function col()
{
    return "<v:div v:width='width|1'><v:self v:slot='slot'/></v:div>";
}

function helloName()
{
    return "hello <v:self v:slot='name'/>";
}

function bold()
{
    return "**<v:self v:slot='slot'/>**";
}

function lorem()
{
    return "Lorem ipsum dolor sit amet, consectetur adipiscing elit. Maecenas euismod ultricies neque vitae molestie. Phasellus ac ante magna. Cras ut orci ac sapien consequat malesuada nec mattis orci. Curabitur risus eros, eleifend eu sem id, posuere faucibus magna. Nam tempus sodales velit, sit amet dictum odio gravida eget. Nulla nisl mauris, accumsan et faucibus in, posuere ut urna. Sed a ante sit amet lorem condimentum tincidunt. Integer maximus at purus et ultrices. Cras a dignissim purus, vel finibus tellus. Suspendisse potenti.";
}

function alink($slot, $url = "")
{
    if (strlen($url) == 0) {
        $url = $slot;
    }
    return "<a href='$url'>$slot</a>";
}

echo \App\Services\Markdown\MarkdownTemplater::render($input, ["app_title" => "SUPER APP", "name" => "world"]);


