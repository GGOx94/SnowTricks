<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\RegistrationFormType;
use App\Repository\UserRepository;
use App\Security\JWTokenSvc;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Security;

class RegistrationController extends AbstractController
{
    private Security $security;
    private MailerInterface $mailer;
    private JWTokenSvc $tokenSvc;

    public function __construct(Security $security, MailerInterface $mailer, JWTokenSvc $tokenSvc)
    {
        $this->security = $security;
        $this->mailer = $mailer;
        $this->tokenSvc = $tokenSvc;
    }

    #[Route('/register', name: 'app_register')]
    public function register(Request $request, UserPasswordHasherInterface $userPasswordHasher, EntityManagerInterface $entityManager): Response
    {
        // This prevents a bug when a logged-in user try to register & click on its registration link in email
        // Register page link is disabled from navbar if user is logged, this should only happen when user access the register route manually
        if($this->security->getUser() != null) {
            return $this->redirectToRoute('app_logout');
        }

        $user = new User();
        $form = $this->createForm(RegistrationFormType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // encode the plain password
            $user->setPassword(
            $userPasswordHasher->hashPassword(
                    $user,
                    $form->get('plainPassword')->getData()
                )
            );

            $entityManager->persist($user);
            $entityManager->flush();

            $expireHours = 2;
            $token = $this->tokenSvc->create(['user_email' => $user->getEmail()], $_ENV['JWTOKEN_SECRET'], $expireHours);

            $email = (new TemplatedEmail())
                ->from('no-reply@p6snowtricks.oc')
                ->to($user->getEmail())
                ->subject( 'Veuillez confirmer votre adresse email')
                ->htmlTemplate('registration/confirmation_email.html.twig')
                ->context(compact('user', 'token', 'expireHours'));

            $this->mailer->send($email);
            $this->addFlash('info', "Un email de confirmation a été envoyé sur l'adresse : ". $user->getEmail());

            return $this->redirectToRoute('app_home');
        }

        return $this->render('registration/register.html.twig', [
            'registrationForm' => $form->createView(),
        ]);
    }

    #[Route('/verify-email/{token}', name: 'app_verify_email_token', methods: ['GET'])]
    public function verifyUserEmail(string $token, UserRepository $userRepo, EntityManagerInterface $manager): Response
    {
        // Invalid token (regex mismatch or forged token with wrong secret key)
        if(!$this->tokenSvc->isRegexValid($token) || !$this->tokenSvc->check($token, $_ENV['JWTOKEN_SECRET'])) {
            $this->addFlash('error', 'Le token de vérification est invalide');
            return $this->redirectToRoute('app_home');
        }

        // Expired token
        if($this->tokenSvc->isExpired($token)) {
            $this->addFlash('error', 'Le token de vérification est expiré');
            return $this->redirectToRoute('app_home');
        }

        $payload = $this->tokenSvc->getPayload($token);
        $user = $userRepo->findOneBy(['email' => $payload['user_email']]);

        // Invalid or already verified user
        if(!$user || $user->isVerified()) {
            $this->addFlash('error', 'L\'utilisateur est déjà vérifié ou est invalide');
            return $this->redirectToRoute('app_login');
        }

        $user->setIsVerified(true);
        $user->addRole("ROLE_USER");
        $manager->persist($user);
        $manager->flush($user);

        $this->addFlash('success', 'Votre adresse email a bien été validée !');

        return $this->redirectToRoute('app_home');
    }
}
