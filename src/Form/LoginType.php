<?php

namespace App\Form;

use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class LoginType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
        ->add('email', EmailType::class, [
            'attr' => ['class' => 'form-control', 'placeholder' => 'Votre adresse email'],
            'label' => 'Adresse e-mail',
        ])
        ->add('password', PasswordType::class, [
            'attr' => ['class' => 'form-control', 'placeholder' => 'Votre mot de passe'],
            'label' => 'Mot de passe',
        ])
        ->add('submit', SubmitType::class, [
            'attr' => ['class' => 'btn btn-primary'],
            'label' => 'Se connecter',
        ]);
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
        ]);
    }
}
