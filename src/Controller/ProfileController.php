<?php

namespace App\Controller;

use App\Service\FileManager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use function PHPUnit\Framework\throwException;

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

        $form = $this->createFormBuilder($user)
            ->add('avatar', FileType::class, [ 'mapped' => false, 'label' => 'Changer mon avatar' ])
            ->add('Enregistrer', SubmitType::class, [ 'attr' => [ 'class' => 'btn btn-success']])
            ->getForm();

        $form->handleRequest($request);

        if($form->isSubmitted() && $form->isValid())
        {
            // TODO UPLOADS : check sizes / types ...
            $avatarFile = $form->get('avatar')->getData();
            if (!$avatarFile) {
                throwException(new \Exception("TODO: handle form->get('avatar')->getData() fail"));
            }

            // Upload the new avatar
            $avatarFileName = $this->fileManager->uploadAvatar($avatarFile);

            // Remove the old avatar
            $this->fileManager->removeAvatar($user->getAvatar());

            $this->manager->persist($user->setAvatar($avatarFileName));
            $this->manager->flush();
        }

        return $this->render('profile/index.html.twig', [
            'last_username' => $user->getName(),
            'formAvatar' => $form->createView(),
            'error' => null
        ]);
    }
}