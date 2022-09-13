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

            return $this->buildJsonResponse($trick, "Vidéo ajoutée avec succès !");
        }

        return $this->buildJsonError("L'url est invalide.<br>
                Veuillez copier-coller l'url d'une vidéo <b>Youtube</b> ou <b>Dailymotion</b>");
    }

    #[Route('/trick/{slug}/edit/video/{id}/edit', name: 'app_trick_edit_video')]
    public function editVideo(string $slug, int $id, Request $req): JsonResponse
    {
        $trick = $this->repo->findOneOr404(['slug' => $slug]);
        $vid = $this->manager->getRepository(Video::class)->find($id);

        $form = $this->createForm(VideoFormType::class, $vid);
        $form->handleRequest($req);

        if($form->isSubmitted() && $form->isValid()) {
            $this->manager->persist($trick);
            $this->manager->flush();
            return $this->buildJsonResponse($trick, "Vidéo modifée avec succès !");
        }

        return $this->buildJsonError("L'url est invalide.<br>
                    Veuillez copier-coller l'url d'une vidéo <b>Youtube</b> ou <b>Dailymotion</b>");
    }

    #[Route('/trick/{slug}/edit/video/{id}/delete', name: 'app_trick_delete_video')]
    public function deleteVideo(string $slug, int $id): JsonResponse
    {
        $trick = $this->repo->findOneOr404(['slug' => $slug]);
        $vid = $this->manager->getRepository(Video::class)->find($id);

        $trick->removeVideo($vid);
        $this->manager->persist($trick);
        $this->manager->flush();

        return $this->buildJsonResponse($trick, "Vidéo supprimée avec succès !");
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

            return $this->buildJsonResponse($trick, "Image ajoutée avec succès");
        }

        return $this->buildJsonError("Erreur :<br>
                    Seules les images <b>.png</b>, <b>.jpg</b> et <b>.bmp</b> de moins de <b>10mo</b> sont acceptées.");
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
            $oldPicName = $pic->getFileName();
            $fileName = $this->fileManager->uploadTrickPicture($pic->getFile(), $slug, $oldPicName);
            $pic->setFileName($fileName);

            $this->manager->persist($trick);
            $this->manager->flush();
            return $this->buildJsonResponse($trick, "Image modifiée avec succès !");
        }

        return $this->buildJsonError("Erreur :<br>
                    Seules les images <b>.png</b>, <b>.jpg</b> et <b>.bmp</b> de moins de <b>10mo</b> sont acceptées.");
    }

    #[Route('/trick/{slug}/edit/picture/{id}/delete', name: 'app_trick_delete_picture')]
    public function deletePicture(string $slug, int $id): JsonResponse
    {
        $trick = $this->repo->findOneOr404(['slug' => $slug]);
        $pic = $this->manager->getRepository(Picture::class);
        $pic = $pic->find($id);

        $this->fileManager->deleteTrickPicture($slug, $pic->getFileName());
        $trick->removePicture($pic);

        $this->manager->persist($trick);
        $this->manager->flush();

        return $this->buildJsonResponse($trick, "Image supprimée avec succès !");
    }

    private function buildJsonResponse(Trick $trick, string $message = null) : JsonResponse
    {
        $template = $this->render('trick/media_carousel.html.twig', [
                'trick' => $trick,
                'picturesUri' => $this->picturesUri
            ])->getContent();

        $response = (new JsonResponse())->setStatusCode(200);
        return $response->setData([
            'template' => $template,
            'message' => $message
        ]);
    }

    private function buildJsonError(string $message) : JsonResponse
    {
        $response = (new JsonResponse())->setStatusCode(200);
        return $response->setData([
            'message' => $message
        ]);
    }
}