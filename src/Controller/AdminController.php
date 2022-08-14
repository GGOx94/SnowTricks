<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class AdminController extends AbstractController
{
    private UserRepository $userRepo;
    private EntityManagerInterface $manager;
    public function __construct(UserRepository $userRepo, EntityManagerInterface $manager)
    {
        $this->userRepo = $userRepo;
        $this->manager = $manager;
    }

    #[Route('/admin', name: 'app_admin')]
    public function index() : Response
    {
        $users = $this->userRepo->findByRoles(['ROLE_USER','ROLE_BANNED']);
        $avatarsUri = $this->getParameter('avatars_uri');
        return $this->render('admin/index.html.twig', [
            'users' => $users,
            'avatarsUri' => $avatarsUri
        ]);
    }

    #[Route('/user/{id}/ban', name: 'app_ban_user')]
    public function banUser(User $user) : Response
    {
        $user->setRole('ROLE_BANNED');
        $this->manager->persist($user);
        $this->manager->flush();

        return $this->redirectToRoute('app_admin');
    }

    #[Route('/user/{id}/unban', name: 'app_unban_user')]
    public function unbanUser(User $user) : Response
    {
        $user->setRole('ROLE_USER');
        $this->manager->persist($user);
        $this->manager->flush();

        return $this->redirectToRoute('app_admin');
    }
}