<?php

namespace App\Controller;

use App\Services\FileManager\FileExplorer;
use App\Services\FileManager\NotAllowedFileException;
use App\Services\Markdown\MarkdownMetadata;
use App\Services\Markdown\MarkdownRenderer;
use App\Services\Markdown\MarkdownTemplater;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Filesystem\Exception\IOException;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;

class HomeController extends AbstractController
{

    #[Route('', name: 'app_home')]
    #[Route('/{path}', name: 'app_render', requirements: ['path' => '(?!_/).*'])]
    public function index(
        FileExplorer      $fileExplorer,
        MarkdownTemplater $markdownRenderer,
        string            $path = ""): Response
    {
        try {
            $content = $fileExplorer->getFile($path);
        } catch (IOException $ioException) {
            $content = "<h1>PAGE NOT FOUND !</h1>";
        } catch (NotAllowedFileException $notAllowedFileException) {
            throw new NotFoundHttpException($notAllowedFileException->getMessage());
        }

        return $this->render('home/index.html.twig', [
            'fileList' => $fileExplorer->listTreeFiles("/", $path),
            'page' => $markdownRenderer->render($path, $content, []),
        ]);

    }
}
