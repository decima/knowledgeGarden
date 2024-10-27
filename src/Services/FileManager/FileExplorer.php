<?php

namespace App\Services\FileManager;

use App\Services\Markdown\MarkdownMetadata;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Filesystem\Exception\IOException;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Path;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;
use function Symfony\Component\DependencyInjection\Loader\Configurator\iterator;

class FileExplorer
{

    public function __construct(
        #[Autowire(param: 'root_path')]
        private string          $rootPath,
        private RouterInterface $router,
        private CacheInterface  $cache,)
    {
        if (str_ends_with($this->rootPath, "/")) $this->rootPath = rtrim($this->rootPath, "/");

    }

    public function absolutePath($path, bool $allowedDotFiles = false)
    {
        if (!str_starts_with($path, "/")) $path = "/" . $path;
        if (!Path::isBasePath($this->rootPath, $this->rootPath . $path)) {
            return $this->rootPath;
        }
        if (!$allowedDotFiles) {
            if (str_starts_with($path, ".") || strpos($path, "/.") > -1) {
                throw new NotAllowedFileException("Not allowed path: " . $path);
            }
        }
        return Path::makeAbsolute($this->rootPath . $path, $this->rootPath);
    }

    public function getFile(string $path, bool $allowDotFiles = false): string
    {

        $fs = new Filesystem();
        $fullPath = $this->absolutePath($path, $allowDotFiles);
        if (is_dir($fullPath)) {
            try {
                return $this->getFile($path . "/readme.md");
            } catch (\Exception $exception) {
            }
        } elseif (!$fs->exists($fullPath) && !str_ends_with($fullPath, ".md")) {
            return $this->getFile($path . ".md", $allowDotFiles);
        }
        return $fs->readFile($fullPath);

    }

    public function writeContent(string $path, string $content, bool $allowDotFiles = false)
    {
        $fs = new Filesystem();
        $fullPath = $this->absolutePath($path, $allowDotFiles);
        $fs->mkdir(Path::getDirectory($fullPath));
        $fs->dumpFile($fullPath, $content);
    }

    public function listFiles(string $path): iterable
    {
        $finder = new Finder();
// find all files in the current directory
        $finder->in($this->absolutePath($path))->depth('0');
        return iterator_to_array($finder);
    }

    public function listTreeFiles(string $path, string $currentPath = ""): array
    {

        $files = [];
        foreach ($this->listFiles($path) as $path => $file) {
            /**
             * @var \SplFileObject $file
             */
            $relative = Path::makeRelative($path, $this->rootPath);
            if (str_starts_with($relative, "_")) {
                continue;
            }
            if (str_ends_with($relative, "/readme.md")) {
                continue;
            }

            $files[$relative] = [
                "link" => $this->router->generate("app_render", ["path" => $relative]),
                "file" => $file,
                "content" => $file->getFilename(),
                "children" => [],
                "selected" => $relative == $currentPath,
            ];
            /**
             * @var \SplFileInfo $file
             */
            if ($file->isDir()) {
                $files[$relative]["children"] = $this->listTreeFiles($relative, $currentPath);
            } else {
                $files[$relative]["content"] = $this->cache->get($relative . ".title", function (ItemInterface $item) use ($relative, $files) {
                    $item->expiresAfter(10);
                    $content = $this->getFile($relative);
                    $meta = MarkdownMetadata::extract($content);
                    return $meta["title"] ?? $files[$relative]["content"];
                });

            }
        }
        return $files;
    }
}