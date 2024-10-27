<?php

namespace App\Services\EventListeners;

use App\Controller\Install\InstallerController;
use App\Kernel;
use App\Services\Configuration\Configuration;
use App\Services\Configuration\InvalidConfigurationException;
use Symfony\Bridge\Twig\Form\TwigRendererEngine;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ControllerEvent;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent;
use Symfony\Component\HttpKernel\Event\KernelEvent;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Routing\RouterInterface;
use Twig\Environment;

class ConfigurationActions implements EventSubscriberInterface
{
    public function __construct(
        private readonly Configuration $configuration,
        private RouterInterface        $router,
        private Environment            $twig,
    )
    {

    }

    public static function getSubscribedEvents()
    {
        return [
            KernelEvents::CONTROLLER => [
                ['RedirectToMissingConfiguration', 100]
            ]
        ];
    }

    public function RedirectToMissingConfiguration(ControllerEvent $event)
    {
        $controller = $event->getController();
        if ($this->configuration->inMemory &&
            is_countable($controller) && (!(count($controller) === 2 && $controller[0]::class === InstallerController::class)
            )) {

            $event->setController(function (Request $request) {
                return new RedirectResponse($this->router->generate('app_install_index'));
            });
        }

    }
}