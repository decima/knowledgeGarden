<?php

namespace App\Controller\Install;

use App\Form\InstallType;
use App\Form\UserRegisterType;
use App\Services\Configuration\Configuration;
use App\Services\Configuration\ConfigurationManager;
use App\Services\Configuration\User;
use App\Services\FileManager\FileExplorer;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\MakerBundle\Validator;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route("/_/install", name: 'app_install')]
class InstallerController extends AbstractController
{


    #[Route('', name: "_index")]
    public function index(
        Configuration               $configuration,
        ConfigurationManager        $configurationManager,
        Request                     $request,
        UserPasswordHasherInterface $passwordHasher,
    ): Response
    {
        if (null !== $configuration->stateAsString) {
            return $this->redirectToRoute('app_install_invalidconfiguration', ['reason' => $configuration->stateAsString]);
        }
        $form = $this->createForm(InstallType::class, $configuration);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            foreach ($configuration->getUsers() as $user) {
                if (null === $user->clearPassword) {
                    continue;
                }
                $hashed = $passwordHasher->hashPassword($user, $user->clearPassword);
                $user->password = $hashed;
                $user->eraseCredentials();
            }
            $configurationManager->save();
            return $this->redirectToRoute("app_home");
        }

        return $this->render('install/installer/index.html.twig', [
            'configuration' => $configuration,
            'form' => $form->createView(),
        ]);
    }

    #[Route("/invalid", name: "_invalidconfiguration")]
    public function invalidConfiguration(Request $request)
    {

        return $this->render('install/installer/invalid.html.twig', ['reason' => $request->get('reason')]);

    }

}
