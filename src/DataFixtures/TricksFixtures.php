<?php

namespace App\DataFixtures;

use App\Entity\Category;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\String\Slugger\AsciiSlugger;

use App\Entity\Trick;
use App\Entity\Comment;
use App\Entity\User;

class TricksFixtures extends Fixture
{
    private UserPasswordHasherInterface $usrPwdHasher;

    public function __construct(UserPasswordHasherInterface  $hashItf)
    {
        $this->usrPwdHasher = $hashItf;
    }

    public function load(ObjectManager $manager): void
    {
        $categs = new ArrayCollection();
        $tricks = new ArrayCollection();
        $slugger = new AsciiSlugger();

        // Create categories (trick groups)
        for($a=0; $a <= 5; $a++)
        {
            $categ = new Category();
            $categ->setLabel("Catégorie no. " . $a);
            $categs[] = $categ;
            $manager->persist($categ);
        }

        // Create tricks
        for($i=1; $i < 15; $i++)
        {
            $trick = new Trick();
            $trick->setTitle("Titre trick : " . $i)
                ->setSlug($slugger->slug($trick->getTitle()))
                ->setDescription("<p>Description trick : ".$i."</p>")
                ->setCategory($categs[mt_rand(0, $categs->count() - 1)])
                ->setCreatedAt(new \DateTimeImmutable());

            $tricks->add($trick);
            $manager->persist($trick);
        }

        // Create users
        for($j=1; $j < 8; $j++)
        {
            $usr = new User();
            $plainPassword = "test" . $j;
            $usr->setName("user-" . $j)
                ->setEmail("user".$j."@fr.fr")
                ->setPassword($this->usrPwdHasher->hashPassword($usr, $plainPassword))
                ->setIsVerified(true);

            $usr->addRole('ROLE_USER');

            $manager->persist($usr);

                // Create comments from user on tricks
                for($k=1; $k < 8; $k++)
                {
                    $chanceToComment = mt_rand(1,3);
                    if($chanceToComment == 3)
                    {
                        $trickCommented = mt_rand(0, $tricks->count() - 1);
                        $com = new Comment();
                        $com->setAuthor($usr)
                            ->setTrick($tricks->get($trickCommented))
                            ->setContent("Contenu du commentaire : ". $k . " --> youpi.")
                            ->setCreatedAt(new \DateTimeImmutable());

                        $manager->persist($com);
                    }
                }
        }

        $manager->flush();
    }
}
