<?php

namespace App\Controller;

use App\Entity\Trick;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use function PHPUnit\Framework\throwException;

class TrickController extends AbstractController
{
    #[Route('/trick/{slug}', name: 'app_trick')]
    public function index(string $slug, ManagerRegistry $doctrine): Response
    {
        $trick = $doctrine->getRepository(Trick::class)->findOneBy(['slug' => $slug]);

        if(!isset($trick)) {
            throwException(new \Exception("This trick doesn't exists"));
        }

        return $this->render('trick/index.html.twig', [
            'controller_name' => 'TrickController',
            'trick' => $trick
        ]);
    }
}
