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
                        message:"L'URL de la vidéo est invalide, veuillez insérer l'URL d'une vidéo Youtube ou Dailymotion.",
                        includeInternalMessages: false
                    ),
            ],
            'required' => false,
            'label' => $options['label'],
            'attr'=>['placeholder' => "Entrez l'URL d'une vidéo Youtube ou Dailymotion"]
        ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Video::class,
            'label' => false
        ]);
    }
}