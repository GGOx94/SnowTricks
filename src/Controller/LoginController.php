<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

class LoginController extends AbstractController
{
    #[Route('/login', name: 'app_login')]
    public function index(AuthenticationUtils $authenticationUtils): Response
    {
        $error = $authenticationUtils->getLastAuthenticationError();

        return $this->render('login/index.html.twig', [
            'error' => $error
        ]);
    }

    #[Route('/login_message', name: 'app_login_message')]
    public function loginMessage() : Response
    {
        $this->addFlash('info', 'Bonjour, '.$this->getUser()->getName().' !');
        return $this->redirectToRoute('app_home');
    }

    #[Route('/logout', name: 'app_logout')]
    public function logout() { }

    #[Route('/logout_message', name: 'app_logout_message')]
    public function logoutMessage() : Response
    {
        $this->addFlash('info', 'Vous vous êtes déconnecté.');
        return $this->redirectToRoute('app_home');
    }
}
