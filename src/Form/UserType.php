<?php

namespace App\Form;

use App\Entity\User;
use App\Entity\Role;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
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
            ->add('username', TextType::class, ['required' => true])
            ->add('roleEntities', EntityType::class, [
                'class' => Role::class,
                'choice_label' => 'displayName',
                'multiple' => true,
                'expanded' => true,
                'label' => 'Roles',
                'help' => 'Selecciona los roles que tendrá este usuario',
                'required' => false,
                'mapped' => false,
                'query_builder' => function ($repository) {
                    return $repository->createQueryBuilder('r')
                        ->where('r.isActive = :active')
                        ->setParameter('active', true)
                        ->orderBy('r.displayName', 'ASC');
                },
                'attr' => [
                    'class' => 'roles-checkboxes'
                ]
            ])
            ->add('legajo', TextType::class, ['required' => false])
            ->add('password', PasswordType::class, ['required' => false, 'empty_data' => 'noPass'])
            ->add('email', EmailType::class, ['required' => true])
            ->add('telefono', TelType::class, ['required' => false])
            ->add('habilitado', ChoiceType::class, ['required' => false, 'choices' => [
                'Si' => 1,
                'No' => 0
            ]])
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
