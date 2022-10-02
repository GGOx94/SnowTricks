<?php

namespace App\DataFixtures;

use App\Entity\Category;
use App\Entity\Comment;
use App\Entity\Picture;
use App\Entity\Trick;
use App\Entity\User;
use App\Entity\Video;
use DateTimeInterface;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Persistence\ObjectManager;
use Exception;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\String\Slugger\AsciiSlugger;
use Symfony\Component\String\Slugger\SluggerInterface;

class RunFixtures extends Fixture
{
    private Filesystem $filesystem;
    private SluggerInterface $slugger;
    private UserPasswordHasherInterface $hasher;
    private string $tricksPicsDir;
    private string $fixturesPicsDir;

    public function __construct(
        Filesystem $filesystem,
        SluggerInterface $slugger,
        UserPasswordHasherInterface $hasher,
        string $tricksPicsDir
    )
    {
        $this->filesystem = $filesystem;
        $this->slugger = $slugger;
        $this->hasher = $hasher;
        $this->tricksPicsDir = $tricksPicsDir;
        $this->fixturesPicsDir = dirname(__FILE__) . '/Data/Pictures/';
    }

    /**
     * @throws Exception
     */
    public function load(ObjectManager $manager)
    {
        $this->purgeDirectories();

        $groups = new ArrayCollection();
        $groups[] = (new Category())->setLabel("Grab");
        $groups[] = (new Category())->setLabel("Rotation");
        $groups[] = (new Category())->setLabel("Flip");
        $groups[] = (new Category())->setLabel("Slide");

        $tricks = new ArrayCollection();
        $slugger = new AsciiSlugger();

        $tricks[] = (new Trick())
            ->setTitle("Mute")
            ->setCategory($groups[0])
            ->setDescription("Saisie de la carre frontside de la planche entre les deux pieds avec la main avant.\nUn grab est d'autant plus réussi que la saisie est longue. De plus, le saut est d'autant plus esthétique que la saisie du snowboard est franche, ce qui permet au rideur d'accentuer la torsion de son corps grâce à la tension de sa main sur la planche.\nOn dit alors que le grab est tweaké (le verbe anglais to tweak signifie « pincer » mais a également le sens de « peaufiner »).");

        $tricks[] = (new Trick())
            ->setTitle("Style Week")
            ->setCategory($groups[0])
            ->setDescription("Saisie de la carre backside de la planche, entre les deux pieds, avec la main avant.\nUn grab est d'autant plus réussi que la saisie est longue. De plus, le saut est d'autant plus esthétique que la saisie du snowboard est franche, ce qui permet au rideur d'accentuer la torsion de son corps grâce à la tension de sa main sur la planche.\nOn dit alors que le grab est tweaké (le verbe anglais to tweak signifie « pincer » mais a également le sens de « peaufiner »).");

        $tricks[] = (new Trick())
            ->setTitle("Truck Driver")
            ->setCategory($groups[0])
            ->setDescription("Saisie du carre avant et carre arrière avec chaque main (comme tenir un volant de voiture).\nUn grab est d'autant plus réussi que la saisie est longue. De plus, le saut est d'autant plus esthétique que la saisie du snowboard est franche, ce qui permet au rideur d'accentuer la torsion de son corps grâce à la tension de sa main sur la planche.\nOn dit alors que le grab est tweaké (le verbe anglais to tweak signifie « pincer » mais a également le sens de « peaufiner »).");

        $tricks[] = (new Trick())
            ->setTitle("Stalefish")
            ->setCategory($groups[0])
            ->setDescription("Saisie de la carre backside de la planche entre les deux pieds avec la main arrière.\nUn grab est d'autant plus réussi que la saisie est longue. De plus, le saut est d'autant plus esthétique que la saisie du snowboard est franche, ce qui permet au rideur d'accentuer la torsion de son corps grâce à la tension de sa main sur la planche.\nOn dit alors que le grab est tweaké (le verbe anglais to tweak signifie « pincer » mais a également le sens de « peaufiner »).");

        $tricks[] = (new Trick())
            ->setTitle("1080 ou Big Foot")
            ->setCategory($groups[1])
            ->setDescription("Trois tours en rotation horizontale.\nUne rotation peut être agrémentée d'un grab, ce qui rend le saut plus esthétique mais aussi plus difficile car la position tweakée a tendance à déséquilibrer le rideur et désaxer la rotation.");

        $tricks[] = (new Trick())
            ->setTitle("Front Flip")
            ->setCategory($groups[2])
            ->setDescription("Rotation en avant dans les airs.\nIl est possible de faire plusieurs flips à la suite, et d'ajouter un grab à la rotation.");

        $tricks[] = (new Trick())
            ->setTitle("Back Flip")
            ->setCategory($groups[2])
            ->setDescription("Rotation en arrière dans les airs.\nLes flips agrémentés d'une vrille existent aussi (Mac Twist, Hakon Flip...), mais de manière beaucoup plus rare, et se confondent souvent avec certaines rotations horizontales désaxées.");

        $tricks[] = (new Trick())
            ->setTitle("Slide")
            ->setCategory($groups[3])
            ->setDescription("Un slide consiste à glisser sur une barre de slide.\nLe slide se fait soit avec la planche dans l'axe de la barre, soit perpendiculaire, soit plus ou moins désaxé.");

        $tricks[] = (new Trick())
            ->setTitle("Nose Slide")
            ->setCategory($groups[3])
            ->setDescription("Slide avec l'avant de la planche sur la barre.");

        $tricks[] = (new Trick())
            ->setTitle("Tail Slide")
            ->setCategory($groups[3])
            ->setDescription("Slide avec l'arrière de la planche sur la barre");

        // Placeholder Tricks
        for($i=0; $i < 15; $i++) {
            $tricks[] = (new Trick())
                ->setTitle("Trick démo-$i")
                ->setCategory($groups[2])
                ->setDescription("Pour les besoins de la démonstration")
                ->setCreatedAt($this->getImmutableDateDaysAgo(7));
        }

        // Load all Tricks medias
        $picFileNames = $this->loadPicturesFixtures();
        $videoUrls = $this->loadVideoUrls();

        foreach ($tricks as $trick)
        {
            if(empty($trick->getCreatedAt())) {
                $trick->setCreatedAt(new \DateTimeImmutable());
            }

            $trick->setSlug($slugger->slug($trick->getTitle()));

            // Set trick pictures
            $trickPics = $this->getRandomEntries($picFileNames, 4);
            foreach ($trickPics as $tPic) {
                $pFName = $this->picUpload($tPic, $trick->getSlug());
                $p = new Picture();
                $p->setFileName($pFName);
                $p->setTrick($trick);
                $manager->persist($p);
            }
            
            // Set trick videos
            $trickVideos = $this->getRandomEntries($videoUrls, 3);
            foreach ($trickVideos as $vidUrl) {
                $v = new Video();
                $v->setEmbedUrl($vidUrl);
                $trick->addVideo($v);
            }
        }

        // Users
        $users = new ArrayCollection();
        $johndoe = new User();
        $johndoe->setName("John Doe")
            ->setEmail("john.doe@gmail.com")
            ->setPassword($this->hasher->hashPassword($johndoe, "Secret123"))
            ->setIsVerified(true);
        $users[] = $johndoe;

        $janedoe = new User();
        $janedoe->setName("Jane Doe")
            ->setEmail("jane.doe@gmail.com")
            ->setPassword($this->hasher->hashPassword($johndoe, "Secret123"))
            ->setIsVerified(true);
        $users[] = $janedoe;

        // Comments from users on tricks
        $comments = $this->generateComments($users, $tricks);

        // ADMIN User
        $admin = new User();
        $admin->setName("Admin")
            ->setEmail("admin@p6.oc")
            ->setPassword($this->hasher->hashPassword($johndoe, "Secret123"))
            ->setIsVerified(true)
            ->addRole('ROLE_ADMIN');
        $users[] = $admin;


        // Persist entities
        foreach ($groups as $grp) {
            $manager->persist($grp);
        }

        foreach ($tricks as $trick) {
            $manager->persist($trick);
        }

        foreach ($users as $user) {
            $manager->persist($user);
        }

        foreach ($comments as $comment) {
            $manager->persist($comment);
        }

        $manager->flush();
    }

    private function loadPicturesFixtures() : array
    {
        $pics = array();

        foreach (scandir($this->fixturesPicsDir) as $pic) {
            if ($pic !== '.' && $pic !== '..') {
                $pics[] = $pic;
            }
        }

        return $pics;
    }

    private function getRandomEntries(array $sourceArray, int $count = 5) : array
    {
        $results = array();
        for($i=0; $i < $count; $i++) {
            $results[] = $sourceArray[mt_rand(0, count($sourceArray) - 1)];
        }

        return $results;
    }

    private function picUpload(string $fileName, string $trickSlug) : string
    {
        $picFile = new File($this->fixturesPicsDir.$fileName);
        $targetDir = $this->tricksPicsDir . $trickSlug;

        if( !$this->filesystem->exists($targetDir) ) {
            $this->filesystem->mkdir($targetDir);
        }

        $safeFilename = $this->slugger->slug($fileName);
        $newFileName = $safeFilename.'-'.uniqid().'.'.$picFile->guessExtension();

        $this->filesystem->copy($picFile->getPathname(), $targetDir.'/'.$newFileName, true);
        return $newFileName;
    }

    private function loadVideoUrls() : array
    {
        $videos = array();
        $videosFilename = dirname(__FILE__) . '/Data/Videos.txt';

        $handle = fopen($videosFilename, "r");
        if ($handle) {
            while (($line = fgets($handle)) !== false) {
                if(!empty($line)) {
                    $videos[] = trim($line);
                }
            }

            fclose($handle);
        }

        return $videos;
    }

    /**
     * @throws Exception
     */
    private function generateComments(ArrayCollection $users, ArrayCollection $tricks) : ArrayCollection
    {
        $comments = new ArrayCollection();

        $lorems = [
            "In vitae tincidunt tellus. Cras congue viverra commodo.\nQuisque ultrices sapien enim, auctor lobortis dolor facilisis at.\n\nAenean dignissim, dolor sit amet mattis ultrices, dolor nunc fermentum ex, ut rhoncus magna dui vitae augue.",
            "Nulla laoreet neque libero, aliquam pretium libero vehicula nec.\nSed quis urna blandit, semper urna vel, aliquam sem.\n\nMauris euismod odio a pretium accumsan.",
            "Ut luctus ex ut placerat interdum.\n\nVivamus hendrerit massa et mauris pretium ultricies.\nConsequat vehicula lacus. Phasellus elementum dapibus nibh eu fermentum.",
            "Aenean volutpat bibendum nisl a interdum.\n\nVivamus sagittis dui a tellus tempor aliquet.",
            "Phasellus interdum nulla metus, ut porta dui pharetra eget.",
            "Donec sit amet mollis ipsum.\n\nDuis suscipit nisl sed scelerisque faucibus."
        ];

        foreach($tricks as $trick)
        {
            $maxCommentsCount = mt_rand(8, 15);
            for($i = 0; $i < $maxCommentsCount; $i++)
            {
                $user = $users[mt_rand(0, $users->count() - 1)];
                $com = new Comment();
                $com->setTrick($trick);
                $com->setAuthor($user);
                $com->setContent($lorems[mt_rand(0, count($lorems) - 1)]);
                $com->setCreatedAt($this->getImmutableDateDaysAgo(mt_rand(0,14)));
                $comments->add($com);
            }
        }

        return $comments;
    }

    /**
     * @throws Exception
     */
    private function getImmutableDateDaysAgo(int $days) : \DateTimeImmutable
    {
        $daysInterval = new \DateInterval("P${days}D");
        $strDate = ((new \DateTime())->sub($daysInterval))->format(DateTimeInterface::ATOM);
        return new \DateTimeImmutable($strDate);
    }

    private function purgeDirectories()
    {
        // Remove, if any, pre-existing trick pictures directories
        foreach (scandir($this->tricksPicsDir) as $element) {
            if ($element !== '.' && $element !== '..') {
                $elFullpath = $this->tricksPicsDir.$element;
                if(is_dir($elFullpath)) {
                    $this->filesystem->remove($elFullpath);
                }
            }
        }
    }
}
