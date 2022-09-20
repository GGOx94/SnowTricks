<?php

namespace App\Controller;

use App\Form\PasswordFormType;
use App\Repository\UserRepository;
use App\Security\JWTokenSvc;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;

class ResetPasswordController extends AbstractController
{
    private JWTokenSvc $tokenSvc;
    private UserRepository $userRepo;
    private EntityManagerInterface $manager;
    public function __construct(JWTokenSvc $tokenSvc, UserRepository $userRepo, EntityManagerInterface $manager)
    {
        $this->tokenSvc = $tokenSvc;
        $this->userRepo =$userRepo;
        $this->manager = $manager;
    }

    #[Route('/reset-password/{token}', name: 'app_reset_password_token')]
    public function resetPassword(Request $req, string $token, UserPasswordHasherInterface $usrPswdHasher,): Response
    {
        // Invalid token (regex mismatch or forged token with wrong secret key)
        if(!$this->tokenSvc->isRegexValid($token) || !$this->tokenSvc->check($token, $_ENV['JWTOKEN_SECRET'])) {
            $this->addFlash('error', 'Le token de réinitialisation de mot de passe est invalide');
            return $this->redirectToRoute('app_home');
        }

        // Expired token
        if($this->tokenSvc->isExpired($token)) {
            $this->addFlash('error', 'Le token de réinitialisation de mot de passe est expiré');
            return $this->redirectToRoute('app_home');
        }

        $payload = $this->tokenSvc->getPayload($token);
        $user = $this->userRepo->findOneBy(['email' => $payload['user_email']]);

        $form = $this->createForm(PasswordFormType::class, null, ['submit' => true]);
        $form->handleRequest($req);

        if ($form->isSubmitted() && $form->isValid()) {
            // encode the plain password
            $plainPswd = $form->get('plainPassword')->getData();
            $user->setPassword( $usrPswdHasher->hashPassword( $user, $plainPswd ));

            $this->manager->persist($user);
            $this->manager->flush($user);

            $this->addFlash('success', 'Votre mot de passe a bien été modifié !');
            return $this->redirectToRoute('app_home');
        }

        return $this->render('login/reset_password.html.twig', [
            'formResetPassword' => $form->createView(),
            'token' => $token
        ]);
    }
}