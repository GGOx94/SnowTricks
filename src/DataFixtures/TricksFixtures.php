<?php

namespace App\DataFixtures;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\String\Slugger\AsciiSlugger;

use App\Entity\Trick;
use App\Entity\Comment;
use App\Entity\User;

class TricksFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        // $product = new Product();
        // $manager->persist($product);

        $tricks = new ArrayCollection();
        $slugger = new AsciiSlugger();

        // Create tricks
        for($i=1; $i <= 5; $i++)
        {
            $trick = new Trick();
            $trick->setTitle("Titre trick : " . $i)
                ->setSlug($slugger->slug($trick->getTitle()))
                ->setDescription("<p>Description trick : ".$i."</p>")
                ->setCategory("TODO-CATEG")
                ->setCreatedAt(new \DateTimeImmutable());

            $tricks->add($trick);
            $manager->persist($trick);
        }

        // Create users
        for($j=1; $j <= 5; $j++)
        {
            $usr = new User();
            $usr->setName("user-" . $j)
                ->setAvatar("default.png")
                ->setEmail("user".$j."@fr.fr")
                ->setPassword("$3cr3t".$j);

            $manager->persist($usr);

                // Create comments from user on tricks
                for($k=1; $k <= 5; $k++)
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
