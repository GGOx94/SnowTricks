<?php

namespace App\Controller;

use App\Entity\Comment;
use App\Form\CommentFormType;
use App\Form\PictureFormType;
use App\Form\TrickFormType;
use App\Form\VideoFormType;
use App\Repository\TrickRepository;
use App\Service\FileManager;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\String\Slugger\AsciiSlugger;

use App\Entity\Trick;

class TrickController extends AbstractController
{
    public function __construct(
        protected readonly FileManager            $fileManager,
        protected readonly EntityManagerInterface $manager,
        protected readonly TrickRepository        $repo
    ) {}

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
    public function edit(Trick $trick, Request $request): Response
    {
        $editTrickForm = $this->createForm(TrickFormType::class, $trick, [ 'edit_mode' => true ]);
        // Keep a copy of the original Trick title before handleRequest(), in case of slug conflicts : see below
        $baseTrickTitle = $trick->getTitle();
        $editTrickForm->handleRequest($request);

        if($editTrickForm->isSubmitted() && $editTrickForm->isValid())
        {
            // Generate a slug in case title has changed and check for conflicts on existing tricks
            $slug = (new AsciiSlugger())->slug($trick->getTitle());
            $existing = $this->repo->findDuplicate($slug, $trick->getId());
            if( $existing )
            {
                $this->addFlash("error", "Ce titre est indisponible !");
                $editTrickForm->addError(new FormError("Ce titre est indisponible !"));
                return $this->renderTrickEditPage($trick->setTitle($baseTrickTitle), $editTrickForm);
            }

            $oldSlug = $trick->getSlug();
            $trick->setSlug($slug);

            $this->manager->flush();

            // If we modified the title (changing its slug), we need to rename its pictures directory too
            if(strcmp($oldSlug, $slug) !== 0) {
                $this->fileManager->renameTrickPicsDir($oldSlug, $slug);
            }

            return $this->redirectToRoute('app_trick', [ 'slug' => $slug ]);
        }

        return $this->renderTrickEditPage($trick, $editTrickForm);
    }

    private function renderTrickEditPage(Trick $trick, FormInterface $editTrickForm) : Response
    {
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

    // TEST HTMX
    #[Route('/trick/{slug}/htmx-desc-test', name: "app_htmx_trick_desc")]
    public function testHtmxDescription(string $slug, Request $request, LoggerInterface $logger): Response
    {
        sleep(2);
        $logger->info("POUET");
        $trick = $this->repo->findOneOr404(['slug' => $slug]);
        $desc = $trick->getDescription();
        return new Response($this->renderView('trick/test-htmx-desc.html.twig', ["desc" => $desc]));
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
