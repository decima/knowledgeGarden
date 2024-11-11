<?php

namespace App\Controller;

use App\Services\Configuration\Configuration;
use App\Services\FileManager\FileExplorer;
use App\Services\FileManager\NoMarkdownException;
use App\Services\FileManager\NotAllowedFileException;
use App\Services\Markdown\MarkdownMetadata;
use App\Services\Markdown\MarkdownRenderer;
use App\Services\Markdown\MarkdownTemplater;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Filesystem\Exception\IOException;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\RouterInterface;

class HomeController extends AbstractController
{

    #[Route('', name: 'app_home')]
    #[Route('/{path}', name: 'app_render', requirements: ['path' => '(?!_/).*'])]
    public function index(
        Configuration     $configuration,
        FileExplorer      $fileExplorer,
        MarkdownTemplater $markdownRenderer,
        string            $path = ""): Response
    {
        try {
            $content = $fileExplorer->getFileContent($path);
        } catch (NoMarkdownException $exception) {
            return new BinaryFileResponse($exception->realPath);
        } catch (IOException) {
            $content = "PAGE NOT FOUND";
        } catch (NotAllowedFileException $notAllowedFileException) {
            throw new NotFoundHttpException($notAllowedFileException->getMessage());
        }

        return $this->render('home/index.html.twig', [
            'app_title' => $configuration->title,
            'fileList' => $fileExplorer->listTreeFiles("/", $path),
            'page' => $markdownRenderer->render($path, $content, []),
        ]);

    }
}
