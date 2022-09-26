<?php

namespace App\Controller;

use App\Form\PictureFormType;
use App\Service\FileManager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

class ProfileController extends AbstractController
{
    private FileManager $fileManager;
    private AuthenticationUtils $authUtils;
    private EntityManagerInterface $manager;

    public function __construct(FileManager $fileManager, AuthenticationUtils $authenticationUtils, EntityManagerInterface $entityManager)
    {
        $this->fileManager = $fileManager;
        $this->authUtils = $authenticationUtils;
        $this->manager = $entityManager;
    }

    #[Route('/profile', name: 'app_profile')]
    public function index(Request $request) : Response
    {
        $error = $this->authUtils->getLastAuthenticationError();

        if($error != null) {
            return $this->render('profile/index.html.twig', [ 'error' => $error ]);
        }

        $user = $this->getUser();
        $avatarsUri = $this->getParameter('avatars_uri');

        $form = $this->createForm(PictureFormType::class, null, ['submit' => true]);
        $form->handleRequest($request);

        if($form->isSubmitted() && $form->isValid())
        {
            $avatarFile = $form->get('file')->getData();

            if(!empty($avatarFile)) {
                // Upload the new avatar
                $avatarFileName = $this->fileManager->uploadAvatar($avatarFile);

                // Remove the old avatar if not default
                $oldAvatar = $user->getAvatar();
                if($oldAvatar !== "default.png") {
                    $this->fileManager->removeAvatar($user->getAvatar());
                }

                $this->manager->persist($user->setAvatar($avatarFileName));
                $this->manager->flush();
            }
        }

        return $this->render('profile/index.html.twig', [
            'avatarsUri' => $avatarsUri,
            'user' => $user,
            'formAvatar' => $form->createView(),
            'error' => null
        ]);
    }
}