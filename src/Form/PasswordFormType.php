<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

use Symfony\Component\Validator\Constraints as Assert;

class PasswordFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('plainPassword', RepeatedType::class, [
                'type' => PasswordType::class,
                'mapped' => false,
                'invalid_message' => 'Les champs de mot de passe ne correspondent pas !',
                'first_options' => [
                    // instead of being set onto the object directly, this is read and encoded in the controller
                    'attr' => ['class' => 'password-field', 'label' => 'Mot de passe'],
                    'constraints' => [
                        new Assert\NotBlank([ 'message' => 'Vous devez entrez un mot de passe' ]),
                        new Assert\Length([
                            'min' => 6, 'minMessage' => 'Votre mot de passe doit faire au moins {{ limit }} caractères.',
                            'max' => 50, 'maxMessage' => 'Votre mot de passe ne peut pas faire plus de {{ limit }} caractères.'
                        ])
                    ],
                    'label' => 'Mot de passe'
                ],
                'required' => true,
                'second_options' => [ 'label' => 'Répéter le mot de passe' ],
            ])

            ->add('submit', SubmitType::class, [
                'attr' => [ 'class' => 'btn btn-success' ],
                'label' => 'Changer mon mot de passe'
            ]);
    }

//    public function configureOptions(OptionsResolver $resolver)
//    {
//        $resolver->setDefaults([
//        ]);
//    }
}