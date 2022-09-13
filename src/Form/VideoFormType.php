<?php

namespace App\Form;

use App\Entity\Video;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

use Symfony\Component\Validator\Constraints as Assert;

class VideoFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $matchYoutube = str_replace('/', '\/', preg_quote(Video::ytUrlStart));
        $matchDailyM = str_replace('/', '\/', preg_quote(Video::dlUrlStart));

        $builder->add('embedUrl', TextType::class, [
            'constraints' => [
                new Assert\AtLeastOneOf( [
                        new Assert\Regex("/{$matchYoutube}/"),
                        new Assert\Regex("/{$matchDailyM}/")
                    ],
                        message:"L'url de la vidéo est invalide, veuillez copier-coller l'url d'une vidéo Youtube ou Dailymotion",
                    ),
            ],
            'required' => false
        ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Video::class,
        ]);
    }
}