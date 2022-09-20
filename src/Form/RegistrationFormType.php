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
                        'max' => 20, 'maxMessage' => 'Votre nom ne peut pas faire plus de {{ limit }} caractères.'
                    ]),
                ],
            ])

            ->add('email', EmailType::class, [
                'constraints' => [
                    new Assert\Email([ 'message' => "Le format de l'email n'est pas valide"])
                ]
            ])

            ->add('plainPassword', PasswordFormType::class, ['mapped' => false, 'label' => false])

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
