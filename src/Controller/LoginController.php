<?php

namespace App\Controller;

use App\Repository\UserRepository;
use App\Security\JWTokenSvc;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

class LoginController extends AbstractController
{
    private JWTokenSvc $tokenSvc;
    private MailerInterface $mailer;

    public function __construct(JWTokenSvc $tokenSvc, MailerInterface $mailer)
    {
        $this->tokenSvc = $tokenSvc;
        $this->mailer = $mailer;
    }

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

    #[Route('/forgot-password', name: 'app_forgot_password')]
    public function forgotPassword(UserRepository $userRepo, Request $req) : Response
    {
        $form = $this->createFormBuilder()
            ->add('username', TextType::class, [
                'label' => false,
                'attr' => ['placeholder' => "Nom d'utilisateur"] ])
            ->add('submit', SubmitType::class, [
                    'attr' => [ 'class' => 'btn btn-success'],
                    'label' => "Demander un nouveau mot de passe"])
            ->getForm();

        $form->handleRequest($req);

        if($form->isSubmitted() && $form->isValid())
        {
            $user = $userRepo->findOneBy(['name' => $form->getData()]);

            $this->addFlash('info', "Un e-mail sera envoyé à cet utilisateur s'il existe.");

            if(!empty($user) && $user->isVerified())
            {
                $secret = $this->getParameter('jwtoken_secret');
                $expireHours = 2;
                $token = $this->tokenSvc->create(['user_email' => $user->getEmail()], $secret, $expireHours);

                $email = (new TemplatedEmail())
                    ->from('reset_password@p6snowtricks.oc')
                    ->to($user->getEmail())
                    ->subject( 'Demande de réinitialisation du mot de passe')
                    ->htmlTemplate('login/reset_password_email.html.twig')
                    ->context(compact('user', 'token', 'expireHours'));

                $this->mailer->send($email);
            }

            return $this->redirectToRoute('app_home');
        }

        return $this->render('login/forgot_password.html.twig', [
            'formPassword' => $form->createView()
        ]);
    }
}
