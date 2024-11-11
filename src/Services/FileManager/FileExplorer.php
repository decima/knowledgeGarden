<?php

namespace App\Services\FileManager;

set_time_limit(6);


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

    public function relativePath($absolute)
    {
        $path = Path::makeRelative($absolute, $this->rootPath);
        if (str_starts_with($path, "..")) {
            return "/";
        }
        return $path;
    }

    public function absolutePath($path = "/", bool $allowedDotFiles = false)
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

    public function getFileContent(string $path, bool $allowDotFiles = false, $ignoreNotMarkdownException = false): string
    {

        $fs = new Filesystem();
        $fullPath = $this->absolutePath($path, $allowDotFiles);
        if (is_dir($fullPath)) {
            try {
                return $this->getFileContent($path . "/readme.md");
            } catch (\Exception $exception) {
            }
        } elseif ($fs->exists($fullPath) && !str_ends_with($fullPath, '.md') && !$ignoreNotMarkdownException) {
            throw new NoMarkdownException($fullPath);
        } elseif (!$fs->exists($fullPath) && !str_ends_with($fullPath, ".md")) {
            return $this->getFileContent($path . ".md", $allowDotFiles);
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
            if (!$file->isDir() && $file->getExtension() !== "md") {
                continue;
            }
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
                "isDir" => $file->isDir(),
                "metadata" => [],
            ];
            /**
             * @var \SplFileInfo $file
             */
            if ($file->isDir()) {
                if (file_exists($file->getRealPath() . "/readme.md")) {
                    $files[$relative]["metadata"] = $this->getMetadataFromFile($file->getRealPath() . "/readme.md");
                    $files[$relative]["content"] = $files[$relative]["metadata"]["title"] ?? $files[$relative]["content"];
                }
                $files[$relative]["children"] = $this->listTreeFiles($relative, $currentPath);
            } else {
                $files[$relative]["metadata"] = $this->getMetadataFromFile($file->getRealPath());
                $files[$relative]["content"] = $files[$relative]["metadata"]["title"] ?? $files[$relative]["content"];

            }
        }
        usort($files, fn($a, $b) => $a["content"] <=> $b["content"]);
        return $files;
    }

    private function getMetadataFromFile($absolutePath)
    {
        dump($absolutePath);
        return MarkdownMetadata::extractFromFileInfo(new \SplFileInfo($absolutePath));
        return $this->cache->get($absolutePath . ".metadata", function (ItemInterface $item) use ($absolutePath) {
            $item->expiresAfter(30);

        });
    }

    private function getMetadataFromRelativeFile($relativePath)
    {
        return $this->getMetadataFromFile($this->absolutePath($relativePath));
    }

    public function getFullSiteContent(): iterable
    {
        $finder = new Finder();
// find all files in the current directory
        $finder->in($this->absolutePath());
        foreach ($finder as $file) {
            $path = $file->getRelativePathname();
            if (str_starts_with($path, "_")) {
                continue;
            }
            if (!$file->isDir() && $file->getExtension() !== "md") {
                continue;
            }
            $f = new File();
            $f->path = "/" . $path;
            $f->filename = $file->getFilename();
            if ($file->isDir() && file_exists($file->getRealPath() . "/readme.md")) {
                $path = $path . "/readme.md";
                $f->content = $this->getFileContent($path);
                $f->metadata = MarkdownMetadata::extract($f->content);

            } else if (!$file->isDir()) {
                $f->content = $this->getFileContent($path);
                $f->metadata = MarkdownMetadata::extract($f->content);
            }

            if ($file->getFilename() === "readme.md") {
                continue;
            }


            yield $f;
        }
    }
}