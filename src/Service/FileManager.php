<?php

namespace App\Service;

use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\String\Slugger\SluggerInterface;
use function PHPUnit\Framework\throwException;

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

    public function uploadTrickPicture(UploadedFile $file, string $trickSlug) : string
    {
        $targetDir = $this->tricksDir . $trickSlug;

        if( !$this->filesystem->exists($targetDir) )
            $this->filesystem->mkdir($targetDir);

        return $this->upload($file, $targetDir);
    }

    public function uploadAvatar(UploadedFile $file) : string
    {
        return $this->upload($file, $this->avatarsDir);
    }

    public function removeAvatar(string $fileName) : void
    {
        $this->filesystem->remove($this->avatarsDir . $fileName);
    }

    private function upload(UploadedFile $file, string $targetDir) : string
    {
        $originalFilename = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
        $safeFilename = $this->slugger->slug($originalFilename);
        $fileName = $safeFilename.'-'.uniqid().'.'.$file->guessExtension();

        try {
            $file->move($targetDir, $fileName);
        } catch (FileException $e) {
            throwException($e);
        }

        return $fileName;
    }
}