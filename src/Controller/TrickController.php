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
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\String\Slugger\AsciiSlugger;

use App\Entity\Trick;

class TrickController extends AbstractController
{
    protected FileManager $fileManager;
    protected EntityManagerInterface $manager;

    protected EntityRepository $repo;

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
            //Generate a slug and check if it already exists before persisting the new Trick
            $slug = (new AsciiSlugger())->slug($trick->getTitle());
            $existing = $this->repo->findBy(['slug' => $slug]);
            if( !empty($existing) )
            {
                $form->addError(new FormError("Ce titre est indisponible"));
                return $this->render('trick/create_trick.html.twig', [
                    'formTrick' => $form->createView()
                ]);
            }

            $trick->setCreatedAt(new \DateTimeImmutable());
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
        $picturesUri = $this->getParameter('tricks_pics_uri');

        return $this->render('trick/edit.html.twig', [
            'trick' => $trick,
            'picturesUri' => $picturesUri,
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

        $comments = $this->manager->getRepository(Comment::class)->findBy(
            ["trick" => $trick],
            ["createdAt" => "DESC"],
            10
        );

        $avatarsUri = $this->getParameter('avatars_uri');
        $picturesUri = $this->getParameter('tricks_pics_uri');

        return $this->render('trick/display.html.twig', [
            'trick' => $trick,
            'comments' => $comments,
            'picturesUri' => $picturesUri,
            'avatarsUri' => $avatarsUri,
            'commentForm' => $form->createView()
        ]);
    }

    #[Route('/trick/{slug}/comments/load/{offset}/{max}', name: 'app_trick_load_comments')]
    public function loadMoreComments(ManagerRegistry $doctrine, string $slug, int $offset, int $max): JsonResponse
    {
        $trick = $this->repo->findOneOr404(['slug' => $slug]);
        $comments = $doctrine->getRepository(Comment::class)
            ->findBy(["trick" => $trick], ["createdAt" => "DESC"], $max, $offset);

        $avatarsUri = $this->getParameter('avatars_uri');

        $template = $this->render('trick/comments.html.twig', [
            'comments' => $comments,
            'avatarsUri' => $avatarsUri
        ])->getContent();

        $response = (new JsonResponse())->setStatusCode(200);
        return $response->setData([
            'template' => $template
        ]);
    }

    private function handleFormPictures(mixed $pictures, string $trickSlug) : void
    {
        foreach ($pictures as $pic)
        {
            $file = $pic->getFile();

            if($file === null) {
                continue;
            }

            $fileName = $this->fileManager->uploadTrickPicture($file, $trickSlug);
            $pic->setFileName($fileName);
        }
    }
}
