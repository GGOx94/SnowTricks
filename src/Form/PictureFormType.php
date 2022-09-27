<?php

namespace App\Form;

use App\Entity\Picture;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

use Symfony\Component\Validator\Constraints as Assert;

class PictureFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('file', FileType::class, [
            'constraints' => [
                new Assert\File([
                    'mimeTypes' => ['image/png', 'image/jpeg', 'image/bmp'],
                    'mimeTypesMessage' => "Mauvais format d'image (sont acceptés les fichier .png, .jpg ou .bmp)",
                    'maxSize' => '10M',
                    'maxSizeMessage' => "L'image est trop volumineuse (max: 10mb)"
                ]),
            ],
            'required'=> false,
            'label' => false
        ]);

        if($options['submit'] === true) {
            $builder->add('submit', SubmitType::class, [
                'attr' => [ 'class' => 'btn btn-success' ],
                'label' => 'Enregistrer'
            ]);
        }
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Picture::class,
            'submit' => false
        ]);
    }
}