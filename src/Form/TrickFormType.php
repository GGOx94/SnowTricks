<?php

namespace App\Form;

use App\Entity\Category;
use App\Entity\Trick;
use App\Entity\Video;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class TrickFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder

            ->add('title', TextType::class, ['label' => 'Titre'])

            ->add('description', TextareaType::class, ['label' => 'Description'])

            ->add('category', EntityType::class, [
                'class' => Category::class,
                'choice_label' => 'label',
                'label' => 'Catégorie'
            ])

//            ->add('picture', FileType::class, [
//                'mapped' => false,
//                'required' => false,
//                'label' => 'Image principale'
//            ])

            ->add('pictures', CollectionType::class, [
                'entry_type' => PictureType::class,
                'entry_options' => ['label' => false],
                'allow_add' => true,
                'by_reference' => false
            ])

            ->add('videos', CollectionType::class, [
                'entry_type' => VideoType::class,
                'entry_options' => ['label' => false ],
                'allow_add' => true,
                'by_reference' => false
            ])

            ->add('submit', SubmitType::class, [
                'attr' => [ 'class' => 'btn btn-success' ],
                'label' => 'Créer le trick'
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Trick::class,
        ]);
    }
}
