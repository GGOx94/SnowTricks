<?php

namespace App\Service;

use Exception;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\String\Slugger\SluggerInterface;

class FileManager
{
    private SluggerInterface $slugger;
    private Filesystem $filesystem;
    private string $avatarsDir;
    private string $tricksDir;

    public function __construct(SluggerInterface $slugger, Filesystem $filesystem, $avatarsDir, string $tricksDir)
    {
        $this->slugger = $slugger;
        $this->filesystem = $filesystem;
        $this->avatarsDir = $avatarsDir;
        $this->tricksDir = $tricksDir;
    }

    public function renameTrickPicsDir(string $oldSlug, string $newSlug) : void
    {
        $oldDir = $this->tricksDir . $oldSlug;
        $newDir = $this->tricksDir . $newSlug;

        if($oldDir === $newDir) {
            return;
        }

        if($this->filesystem->exists($oldDir)) {
            $this->filesystem->rename($oldDir, $newDir );
        }
    }

    public function uploadTrickPicture(UploadedFile $file, string $trickSlug) : string
    {
        $targetDir = $this->tricksDir . $trickSlug;

        if( !$this->filesystem->exists($targetDir) ) {
            $this->filesystem->mkdir($targetDir);
        }

        return $this->upload($file, $targetDir);
    }

    public function deleteTrickPicture(string $trickSlug, string $fileName) : void
    {
        $targetDir = $this->tricksDir . $trickSlug;

        $targetPicture = $targetDir . "/". $fileName;
        if($this->filesystem->exists($targetPicture)) {
            $this->filesystem->remove($targetPicture);
        }
    }

    public function uploadAvatar(UploadedFile $file) : string
    {
        return $this->upload($file, $this->avatarsDir);
    }

    public function removeTrickPicsDir(string $trickSlug) : void
    {
        $targetDir = $this->tricksDir . $trickSlug;

        if( $this->filesystem->exists($targetDir) ) {
            $this->filesystem->remove($targetDir);
        }
    }

    public function removeAvatar(string $fileName) : void
    {
        $targetFile = $this->avatarsDir . $fileName;

        if( $this->filesystem->exists($targetFile) ) {
            $this->filesystem->remove($targetFile);
        }
    }

    /**
     * @throws Exception
     */
    private function upload(UploadedFile $file, string $targetDir) : string
    {
        $originalFilename = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
        $safeFilename = $this->slugger->slug($originalFilename);
        $fileName = $safeFilename.'-'.uniqid().'.'.$file->guessExtension();

        try {
            $file->move($targetDir, $fileName);
        } catch (FileException $e) {
            throw new Exception("Exception levée pendant le déplacement du fichier dans son dossier : " . $e);
        }

        return $fileName;
    }
}