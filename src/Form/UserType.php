<?php

namespace App\Form;

use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TelType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class UserType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('username', TextType::class, ['required' => false])
            ->add('roles', ChoiceType::class, ['choices'  => [
                'Administrador' => "ROLE_ADMIN",
                'Operador' => "ROLE_USER",
            ],
                'multiple'=>true,
                'expanded'=>true,
            ])
            ->add('legajo', TextType::class, ['required' => false])
            /*->add('password', PasswordType::class, [
                'required' => false,
            ])*/
            ->add('email', EmailType::class, ['required' => false])
            ->add('telefono', TelType::class, ['required' => false])
            ->add('save', SubmitType::class, ['label' => 'Guardar'])
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => User::class,
        ]);
    }
}
