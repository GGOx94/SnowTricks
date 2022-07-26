<?php

namespace App\Entity;

use App\Repository\VideoRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: VideoRepository::class)]
class Video
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private int $id;

    #[ORM\ManyToOne(targetEntity: Trick::class, inversedBy: 'videos')]
    #[ORM\JoinColumn(nullable: false)]
    private Trick $trick;

    #[ORM\Column(type: 'string', length: 80)]
    private string $embedUrl;

    // Those properties are used in the setEmbedUrl() function and the constraints of the VideoFormType
    public const ytUrlStart = "https://www.youtube.com/watch?v=";
    public const dlUrlStart = "https://www.dailymotion.com/video/";

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTrick(): ?Trick
    {
        return $this->trick;
    }

    public function setTrick(?Trick $trick): self
    {
        $this->trick = $trick;

        return $this;
    }

    public function getEmbedUrl(): ?string
    {
        return $this->embedUrl;
    }

    public function setEmbedUrl(string $videoUrl): self
    {
        if(str_contains($videoUrl, "/embed/")) {
            $this->embedUrl = $videoUrl;
            return $this;
        }

        if(str_contains($videoUrl, self::ytUrlStart))
        {
            $chanPos = strpos($videoUrl, "&ab_channel");
            if($chanPos !== false) {
                $videoUrl = substr($videoUrl, 0, $chanPos);
            }

            $vidId = str_replace(self::ytUrlStart, "", $videoUrl);
            $this->embedUrl = ("https://www.youtube.com/embed/" . $vidId);
        }
        elseif (str_contains($videoUrl, self::dlUrlStart))
        {
            $vidId = trim($videoUrl, self::dlUrlStart);
            $this->embedUrl = ("https://www.dailymotion.com/embed/video/" . $vidId);
        }

        return $this;
    }
}
