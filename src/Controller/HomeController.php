<?php

namespace App\Controller;

use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

use App\Entity\Trick;

class HomeController extends AbstractController
{
    #[Route('/', name: 'app_home')]
    public function index(ManagerRegistry $doctrine): Response
    {
        $tricks = $doctrine->getRepository(Trick::class)->findBy([], ["createdAt" => "DESC"], 10);
        $picturesUri = $this->getParameter('tricks_pics_uri');

        return $this->render('home/index.html.twig', [
            'controller_name' => 'HomeController',
            'tricks' => $tricks,
            'picturesUri' => $picturesUri
        ]);
    }

    #[Route('/tricks/load/{offset}/{max}', name: 'app_home_load_tricks')]
    public function loadMore(ManagerRegistry $doctrine, int $offset, int $max): JsonResponse
    {
        $tricks = $doctrine->getRepository(Trick::class)->findBy([], ["createdAt" => "DESC"], $max, $offset);
        $picturesUri = $this->getParameter('tricks_pics_uri');

        $template = $this->render('home/tricks.html.twig', [
            'tricks' => $tricks,
            'picturesUri' => $picturesUri
        ])->getContent();

        $response = (new JsonResponse())->setStatusCode(200);
        return $response->setData([
            'template' => $template,
            'tricksCount' => count($tricks)
        ]);
    }
}
