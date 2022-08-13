<?php

namespace App\Controller;

use App\Form\TrickFormType;
use App\Service\FileManager;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\String\Slugger\AsciiSlugger;

use App\Entity\Trick;

class TrickController extends AbstractController
{
    private FileManager $fileManager;
    private EntityManagerInterface $manager;
    private EntityRepository $repo;

    public function __construct(FileManager $fileManager, EntityManagerInterface $entityManager)
    {
        $this->fileManager = $fileManager;
        $this->manager = $entityManager;
        $this->repo = $entityManager->getRepository(Trick::class);
    }

    #[Route('/trick/new', name: 'app_trick_new')]
    public function create(Request $request): Response
    {
        $trick = new Trick();

        $form = $this->createForm(TrickFormType::class, $trick);
        $form->handleRequest($request);

        if($form->isSubmitted() && $form->isValid())
        {
            $trick->setCreatedAt(new \DateTimeImmutable());
            $slug = (new AsciiSlugger())->slug($trick->getTitle());
            $trick->setSlug($slug);

            $this->handleFormPictures($form->get('pictures')->getData(), $slug);

            $this->manager->persist($trick);
            $this->manager->flush();

            return $this->redirectToRoute('app_trick', [ 'slug' => $slug ]);
        }

        return $this->render('trick/create_trick.html.twig', [
            'formTrick' => $form->createView()
        ]);
    }

    #[Route('/trick/{slug}/edit', name: 'app_trick_edit')]
    public function edit(string $slug, Request $request): Response
    {
        $trick = $this->repo->findOneBy(['slug' => $slug]);
        //TODO check trick found

        $form = $this->createForm(TrickFormType::class, $trick);
        //TODO do not remove pictures & videos, use those fields to add, buttons on existing pics & vids to update/delete
        $form->remove('pictures'); // Remove the picture file picker field from the form when editing tricks
        $form->handleRequest($request);

        if($form->isSubmitted() && $form->isValid())
        {
            $slug = (new AsciiSlugger())->slug($trick->getTitle());
            $trick->setSlug($slug);

            //TODO namechange picture dir ? use id instead of slug for dir ? don't use subdirs ?

            $this->manager->persist($trick);
            $this->manager->flush();

            return $this->redirectToRoute('app_trick', [ 'slug' => $slug ]);
        }

        $picturesUri = $this->getParameter('tricks_pics_uri');

        $pictures = $trick->getPictures();

        return $this->render('trick/edit.html.twig', [
            'trick' => $trick,
            'picturesUri' => $picturesUri,
            'trickForm' => $form->createView()
        ]);
    }

//    #[Route('/trick/{slug}/addVideo', name: 'app_trick_add_video')]
//    public function edit(string $slug, Request $request): Response
//    {
//
//    }

    #[Route('/trick/{slug}', name: 'app_trick')]
    public function display(string $slug): Response
    {
        $trick = $this->repo->findOneBy(['slug' => $slug]);

        //TODO check trick found

        $avatarsUri = $this->getParameter('avatars_uri');
        $picturesUri = $this->getParameter('tricks_pics_uri');

        return $this->render('trick/display.html.twig', [
            'trick' => $trick,
            'picturesUri' => $picturesUri,
            'avatarsUri' => $avatarsUri,
        ]);
    }

    private function handleFormPictures(ArrayCollection $pictures, string $trickSlug) : void
    {
        if (!$pictures->isEmpty())
        {
            foreach ($pictures as $pic)
            {
                $pic->setFileName($this->fileManager->uploadTrickPicture($pic->getFile(), $trickSlug));
            }
        }
    }
}
