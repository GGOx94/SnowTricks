<?php

namespace App\Form;

use App\Entity\User;
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
        $builder->add('plainPassword', RepeatedType::class, [
            'type' => PasswordType::class,
            'invalid_message' => 'Les champs de mot de passe ne correspondent pas !',
            'first_options' => [
                'attr' => ['class' => 'password-field', 'label' => 'Mot de passe'],
                'constraints' => [
                    new Assert\Regex('/^(?=.*[A-Za-z])(?=.*\d).{8,30}$/',
                        "Votre mot de passe doit faire 8 à 30 caractères et contenir au moins une lettre et un chiffre")
                ],
                'label' => 'Mot de passe'
            ],
            'required' => true,
            'second_options' => [ 'label' => 'Répéter le mot de passe' ],
        ]);

        if($options['submit'] === true) {
            $builder->add('submit', SubmitType::class, [
                'attr' => [ 'class' => 'btn btn-success' ],
                'label' => 'Changer mon mot de passe'
            ]);
        }
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'submit' => false
        ]);
    }
}