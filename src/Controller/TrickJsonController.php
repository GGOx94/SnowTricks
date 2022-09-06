<?php

namespace App\Controller;

use App\Entity\Picture;
use App\Entity\Trick;
use App\Entity\Video;
use App\Form\PictureFormType;
use App\Form\VideoFormType;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class TrickJsonController extends TrickController
{
    // TODO : flash error & redirect to trick edit if invalid embed vid url

    #[Route('/trick/{slug}/edit/video/add', name: 'app_trick_add_video')]
    public function addVideo(string $slug, Request $req): JsonResponse
    {
        $trick = $this->repo->findOneOr404(['slug' => $slug]);
        $vid = new Video();

        $form = $this->createForm(VideoFormType::class, $vid);
        $form->handleRequest($req);

        if($form->isSubmitted() && $form->isValid())
        {
            $trick->addVideo($vid);
            $this->manager->persist($trick);
            $this->manager->flush();
        }

        return $this->buildJsonResponse($trick);
    }

    #[Route('/trick/{slug}/edit/video/{id}/edit', name: 'app_trick_edit_video')]
    public function editVideo(string $slug, int $id, Request $req): JsonResponse
    {
        $trick = $this->repo->findOneOr404(['slug' => $slug]);
        $vid = $this->manager->getRepository(Video::class)->find($id);

        $form = $this->createForm(VideoFormType::class, $vid);
        $form->handleRequest($req);

        if($form->isSubmitted() && $form->isValid())
        {
            $this->manager->persist($trick);
            $this->manager->flush();
        }

        return $this->buildJsonResponse($trick);
    }

    #[Route('/trick/{slug}/edit/video/{id}/delete', name: 'app_trick_delete_video')]
    public function deleteVideo(string $slug, int $id): JsonResponse
    {
        $trick = $this->repo->findOneOr404(['slug' => $slug]);
        $vid = $this->manager->getRepository(Video::class)->find($id);

        $trick->removeVideo($vid);
        $this->manager->persist($trick);
        $this->manager->flush();

        return $this->buildJsonResponse($trick);
    }

    #[Route('/trick/{slug}/edit/picture/add', name: 'app_trick_add_picture')]
    public function addPicture(string $slug, Request $req): JsonResponse
    {
        $trick = $this->repo->findOneOr404(['slug' => $slug]);
        $pic = new Picture();

        $form = $this->createForm(PictureFormType::class, $pic);
        $form->handleRequest($req);

        if($form->isSubmitted() && $form->isValid())
        {
            $fileName = $this->fileManager->uploadTrickPicture($pic->getFile(), $slug);
            $pic->setFileName($fileName);
            $trick->addPicture($pic);

            $this->manager->persist($trick);
            $this->manager->flush();
        }

        return $this->buildJsonResponse($trick);
    }

    #[Route('/trick/{slug}/edit/picture/{id}/edit', name: 'app_trick_edit_picture')]
    public function editPicture(string $slug, int $id, Request $req): JsonResponse
    {
        $trick = $this->repo->findOneOr404(['slug' => $slug]);
        $pic = $this->manager->getRepository(Picture::class);
        $pic = $pic->find($id);

        $form = $this->createForm(PictureFormType::class, $pic);
        $form->handleRequest($req);

        if($form->isSubmitted() && $form->isValid())
        {
            $newPic = $pic->getFile();
            $oldPicName = $pic->getFileName();
            $fileName = $this->fileManager->uploadTrickPicture($newPic, $slug);
            $this->fileManager->deleteTrickPicture($slug, $oldPicName);
            $pic->setFileName($fileName);

            $this->manager->persist($trick);
            $this->manager->flush();
        }

        return $this->buildJsonResponse($trick);
    }

    #[Route('/trick/{slug}/edit/picture/{id}/delete', name: 'app_trick_delete_picture')]
    public function deletePicture(string $slug, int $id): JsonResponse
    {
        // TODO checkNulls on quick deletes, send back 500
        $trick = $this->repo->findOneOr404(['slug' => $slug]);
        $pic = $this->manager->getRepository(Picture::class);
        $pic = $pic->find($id);

        $this->fileManager->deleteTrickPicture($slug, $pic->getFileName());
        $trick->removePicture($pic);

        $this->manager->persist($trick);
        $this->manager->flush();

        return $this->buildJsonResponse($trick);
    }

    private function buildJsonResponse(Trick $trick) : JsonResponse
    {
        $template = $this->render('trick/media_carousel.html.twig', [
                'trick' => $trick,
                'picturesUri' => $this->picturesUri
            ])->getContent();

        $response = (new JsonResponse())->setStatusCode(200);
        return $response->setData(['template' => $template ]);
    }
}