<?php

namespace App\Controller;

use App\Entity\Category;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\String\Slugger\AsciiSlugger;

use App\Entity\Trick;

class TrickController extends AbstractController
{
    //TODO route edit here too, check trick->getId() for modif date / twig var edit=true
    #[Route('/trick/new', name: 'app_trick_new')]
    public function create(Request $request, EntityManagerInterface $manager): Response
    {
        $trick = new Trick();

        $form = $this->createFormBuilder($trick)
            ->add('title', TextType::class, ['label' => 'Nom'])
            ->add('description', TextType::class, ['label' => 'Description'])
            ->add('category', EntityType::class, [
                'class' => Category::class,
                'choice_label' => 'label',
            ])
            //TODO upload image(s) / link embed video(s) (TWIG side perhaps ?)
            ->add('save', SubmitType::class, ['label' => 'Ajouter le trick'])
            ->getForm();

        $form->handleRequest($request);

        if($form->isSubmitted() && $form->isValid())
        {
            $trick->setCreatedAt(new \DateTimeImmutable());
            $slug = (new AsciiSlugger())->slug($trick->getTitle());
            $trick->setSlug($slug);

            // TODO : create subdir for pictures in public/upload/pictures/tricks/[SLUG]

            $manager->persist($trick);
            $manager->flush();

            return $this->redirectToRoute('app_trick', [ 'slug' => $slug ]);
        }

        return $this->render('trick/create_trick.html.twig', [
            'controller_name' => 'TrickController',
            'formTrick' => $form->createView()
        ]);
    }

    #[Route('/trick/{slug}', name: 'app_trick')]
    public function index(string $slug, ManagerRegistry $doctrine): Response
    {
        $trickRepo = $doctrine->getRepository(Trick::class);
        $trick = $trickRepo->findOneBy(['slug' => $slug]);

        //TODO HANDLE TRICK NULL HERE (404 ?)

        $mainPicture = "/upload/pictures/tricks/";
        $pictures = $trick->getPictures();
        if($pictures->isEmpty()) {
            $mainPicture .= "default.png";
        } else {
            $mainPicture .= $pictures[0]->getFileName();
        }

        return $this->render('trick/index.html.twig', [
            'controller_name' => 'TrickController',
            'trick' => $trick,
            'mainPicture' => $mainPicture
        ]);
    }
}
