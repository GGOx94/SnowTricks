<?php

namespace App\Form;

use App\Entity\Category;
use App\Entity\Trick;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

use Symfony\Component\Validator\Constraints as Assert;

class TrickFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('title', TextType::class, [
                'constraints' => [
                    new Assert\Length([
                        'min' => 5, 'minMessage' => 'Le titre doit faire au moins {{ limit }} caractères.',
                        'max' => 30, 'maxMessage' => 'Le titre ne peut pas faire plus de {{ limit }} caractères.'
                    ]),
                    new Assert\NotBlank([ 'message' => 'Vous devez entrez un titre pour le Trick' ]),
                    new Assert\Regex("/^[\pL\pZ\pN'-]*$/u",
                        'Certains caractères spéciaux sont interdits dans le titre du Trick'),
                ],
                'label' => 'Titre'
            ])

            ->add('description', TextareaType::class, ['label' => 'Description'])

            ->add('category', EntityType::class, [
                'class' => Category::class,
                'choice_label' => 'label',
                'label' => 'Catégorie'
            ]);

        // Disable file pickers for pictures and text fields for videos url in 'edit' mode
        // While editing a trick, there will be dedicated buttons to add new pictures or embed videos
        if(!$options['edit_mode'])
        {
            $builder
                ->add('pictures', CollectionType::class, [
                    'entry_type' => PictureFormType::class,
                    'entry_options' => ['label' => false],
                    'allow_add' => true,
                    'by_reference' => false,
                    'label' => 'Images'
                ])

                ->add('videos', CollectionType::class, [
                    'entry_type' => VideoFormType::class,
                    'entry_options' => ['label' => false ],
                    'allow_add' => true,
                    'by_reference' => false,
                    'label' => 'Vidéos'
                ]);
        }

        $builder->add('submit', SubmitType::class, [
            'attr' => [ 'class' => 'btn btn-success' ],
            'label' => $options['edit_mode'] ? 'Sauvegarder' : 'Créer le trick'
        ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Trick::class,
            'edit_mode' => false
        ]);
    }
}
