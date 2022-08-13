<?php

namespace App\Form;

use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

use Symfony\Component\Validator\Constraints as Assert;

class RegistrationFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder

            ->add('name', TextType::class, [
                'label' => 'Nom d\'utilisateur',
                'constraints' => [
                    new Assert\NotBlank([ 'message' => 'Vous devez entrez un nom' ]),
                    new Assert\Length([
                        'min' => 3, 'minMessage' => 'Votre nom doit faire au moins {{ limit }} caractères.',
                        'max' => 20, 'maxMessage' => 'Votre nom ne peux pas faire plus de {{ limit }} caractères.'
                    ]),
                ],
            ])

            ->add('email', EmailType::class, [
                'constraints' => [
                    new Assert\Email([ 'message' => "Le format de l'email n'est pas valide"])
                ]
            ])

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
                            'max' => 50, 'maxMessage' => 'Votre mot de passe ne peux pas faire plus de {{ limit }} caractères.'
                        ]),
                    ],
                    'label' => 'Mot de passe'
                ],
                'required' => true,
                'second_options' => [ 'label' => 'Répéter le mot de passe' ],
            ])

            ->add('submit', SubmitType::class, [
                'attr' => [ 'class' => 'btn btn-success' ],
                'label' => 'Créer un compte'
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
        ]);
    }
}
