<?php

namespace App\Controller;

use App\Entity\Comment;
use App\Form\CommentFormType;
use App\Form\PictureFormType;
use App\Form\TrickFormType;
use App\Form\VideoFormType;
use App\Service\FileManager;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\String\Slugger\AsciiSlugger;

use App\Entity\Trick;

class TrickController extends AbstractController
{
    protected FileManager $fileManager;
    protected EntityManagerInterface $manager;

    protected EntityRepository $repo;
    protected string $picturesUri;

    public function __construct(FileManager $fileManager, EntityManagerInterface $entityManager, string $picturesUri)
    {
        $this->fileManager = $fileManager;
        $this->manager = $entityManager;

        $this->repo = $entityManager->getRepository(Trick::class);
        $this->picturesUri = $picturesUri;
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

        $editTrickForm = $this->createForm(TrickFormType::class, $trick, [ 'edit_mode' => true ]);

        $editTrickForm->handleRequest($request);

        if($editTrickForm->isSubmitted() && $editTrickForm->isValid())
        {
            $slug = (new AsciiSlugger())->slug($trick->getTitle());
            $oldSlug = $trick->getSlug();
            $trick->setSlug($slug);

            $this->manager->persist($trick);
            $this->manager->flush();

            // If we modified the title (changing its slug), we need to rename its pictures directory too
            if(strcmp($oldSlug, $slug) !== 0) {
                $this->fileManager->renameTrickPicsDir($oldSlug, $slug);
            }

            return $this->redirectToRoute('app_trick', [ 'slug' => $slug ]);
        }

        $editPicForm = $this->createForm(PictureFormType::class);
        $editVidForm = $this->createForm(VideoFormType::class);

        return $this->render('trick/edit.html.twig', [
            'trick' => $trick,
            'picturesUri' => $this->picturesUri,
            'formEditTrick' => $editTrickForm->createView(),
            'formEditPicture' => $editPicForm->createView(),
            'formEditVideo' => $editVidForm->createView(),
            'editMode'=>true
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

    private function handleFormPictures(mixed $pictures, string $trickSlug) : void
    {
        foreach ($pictures as $pic)
        {
            $file = $pic->getFile();
            $fileName = $this->fileManager->uploadTrickPicture($file, $trickSlug);
            $pic->setFileName($fileName);
        }
    }
}
