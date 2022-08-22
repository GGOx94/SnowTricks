<?php

namespace App\Controller;

use App\Entity\Comment;
use App\Form\CommentFormType;
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
        $trick = $this->repo->findOneOr404(['slug' => $slug]);

        $form = $this->createForm(TrickFormType::class, $trick);

        // Remove the pictures & videos form field
        // When editing a trick, each element can be updated/deleted separately
        $form->remove('pictures');
        $form->remove('videos');

        $form->handleRequest($request);

        if($form->isSubmitted() && $form->isValid())
        {
            $slug = (new AsciiSlugger())->slug($trick->getTitle());
            $trick->setSlug($slug);

            $this->manager->persist($trick);
            $this->manager->flush();

            return $this->redirectToRoute('app_trick', [ 'slug' => $slug ]);
        }

        $picturesUri = $this->getParameter('tricks_pics_uri');

        return $this->render('trick/edit.html.twig', [
            'trick' => $trick,
            'picturesUri' => $picturesUri,
            'trickForm' => $form->createView()
        ]);
    }

    #[Route('/trick/{slug}/delete', name: 'app_trick_delete')]
    public function delete(string $slug) : Response
    {
        $trick = $this->repo->findOneOr404(['slug' => $slug]);

        $this->manager->remove($trick);
        $this->manager->flush();
        $this->fileManager->removeTrickPicsDir($slug);

        $this->addFlash('success', 'Le trick "'.$trick->getTitle().'" a bien été supprimé');

        return $this->redirectToRoute('app_home');
    }

//    #[Route('/trick/{slug}/addVideo', name: 'app_trick_add_video')]
//    public function edit(string $slug, Request $request): Response
//    {
//
//    }

    #[Route('/trick/{slug}', name: 'app_trick')]
    public function display(string $slug, Request $request): Response
    {
        $trick = $this->repo->findOneOr404(['slug' => $slug]);

        $comment = new Comment();

        $form = $this->createForm(CommentFormType::class, $comment);
        $form->handleRequest($request);

        if($form->isSubmitted() && $form->isValid())
        {
            $comment->setTrick($trick);
            $comment->setAuthor($this->getUser());
            $comment->setCreatedAt(new \DateTimeImmutable());

            $this->manager->persist($comment);
            $this->manager->flush();

            return $this->redirectToRoute('app_trick', [ 'slug' => $slug ]);
        }

        $avatarsUri = $this->getParameter('avatars_uri');
        $picturesUri = $this->getParameter('tricks_pics_uri');

        return $this->render('trick/display.html.twig', [
            'trick' => $trick,
            'picturesUri' => $picturesUri,
            'avatarsUri' => $avatarsUri,
            'commentForm' => $form->createView()
        ]);
    }

    private function handleFormPictures(ArrayCollection $pictures, string $trickSlug) : void
    {
        if (!$pictures->isEmpty()) {
            foreach ($pictures as $pic) {
                $file = $pic->getFile();
                $fileName = $this->fileManager->uploadTrickPicture($file, $trickSlug);
                $pic->setFileName($fileName);
            }
        }
    }
}
