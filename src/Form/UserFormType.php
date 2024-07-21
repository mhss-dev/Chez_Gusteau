<?php

namespace App\Form;

use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Vich\UploaderBundle\Form\Type\VichImageType;


class UserFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
        ->add('firstname',TextType::class, [
            'label' => 'userForm.firstname'
        ])
        ->add('lastname',TextType::class, [
            'label' => 'userForm.lastname'
        ])
        ->add('imageFile', VichImageType::class, [
            'label' => 'userForm.imageFile',
            'required' => false,
            'allow_delete' => true,
            'delete_label' => "Delete your image?",
            'download_uri' => true,
            'image_uri' => true,
            'asset_helper' => true,
            'imagine_pattern' => 'avatar_thumbnail'
        ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
        ]);
    }
}
