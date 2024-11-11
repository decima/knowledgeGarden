<?php

namespace App\Services\Markdown;

use App\Services\Configuration\Configuration;
use App\Services\FileManager\FileExplorer;
use League\CommonMark\Environment\Environment;
use League\CommonMark\Event\DocumentPreParsedEvent;
use League\CommonMark\Extension\CommonMark\CommonMarkCoreExtension;
use League\CommonMark\Extension\HeadingPermalink\HeadingPermalinkExtension;
use League\CommonMark\Extension\Table\TableExtension;
use League\CommonMark\Extension\TableOfContents\TableOfContentsExtension;
use League\CommonMark\Input\MarkdownInput;
use League\CommonMark\MarkdownConverter;
use Symfony\Component\Filesystem\Path;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class MarkdownRenderer
{

    public function __construct(
        private readonly Configuration         $configuration,
        private readonly FileExplorer          $fileExplorer,
        private readonly UrlGeneratorInterface $urlGenerator,
        private readonly \Twig\Environment     $twig,
    )
    {
    }


    public function Render(string $input): string
    {

        $config = [
            'table_of_contents' => [
                'html_class' => 'table-of-contents',
                'position' => 'top',
                'style' => 'bullet',
                'min_heading_level' => 1,
                'max_heading_level' => 3,
                'normalize' => 'relative',
                'placeholder' => null,
            ],
        ];

        $environment = new Environment($config);
        $environment->addExtension(new CommonMarkCoreExtension());

// Add the two extensions
        $environment->addExtension(new HeadingPermalinkExtension());
        $environment->addExtension(new TableOfContentsExtension());
        $environment->addExtension(new TableExtension());

        $environment->addEventListener(DocumentPreParsedEvent::class, $this->renderSubPage(...), -100);


        $converter = new MarkdownConverter($environment);
        return $converter->convert($input);

    }

    public function renderSubPage(DocumentPreParsedEvent $event)
    {

        $content = $event->getMarkdown()->getContent();
        $content = "<div class='content'>" . $this->renderMarkdown($content) . "</div>";

        $event->replaceMarkdown(new MarkdownInput($content));
    }


    private function renderMarkdown($content, $parameters = [])
    {

        $parameters["app_config"] = $this->configuration;
        $content = $this->twig->createTemplate($content)->render($parameters);
        $content = preg_replace_callback(
            '/\{-(.+?(?=$1))-\}/ms',
            function ($matches) {

                $args = explode("|", trim($matches[1]));
                $templateName = array_shift($args);

                foreach ($args as $k => $arg) {
                    $args["arg_" . $k] = $arg;
                    $named = explode(":", $arg);
                    if (count($named) > 1) {
                        $args[array_shift($named)] = join(":", $named);
                    }
                }
                $subfileContent = "";

                $path = "_templates/" . urlencode($templateName);
                try {
                    $subfileContent = $this->renderMarkdown($this->fileExplorer->getFileContent($path), $args);

                } catch (\Exception $e) {
                    $builtins = __DIR__ . "/builtins";
                    $filePath = Path::makeAbsolute(urlencode($templateName), $builtins);
                    if (Path::getLongestCommonBasePath($builtins, $filePath) === $builtins && file_exists($filePath)) {

                        return $this->renderMarkdown(file_get_contents($filePath), $args);
                    }
                    $url = $this->urlGenerator->generate("app_home", ['path' => $path]);
                    $subfileContent = "<a href='$url'>$matches[0]</a>";
                }
                return $subfileContent;
            },
            $content,
        );
        return $content;

    }

}