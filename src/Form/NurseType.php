<?php

namespace App\Form;

use App\Entity\Nurse;
use App\Entity\Role;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class NurseType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('nombre', TextType::class, [
                'label' => 'Nombre',
                'required' => true,
            ])
            ->add('apellido', TextType::class, [
                'label' => 'Apellido',
                'required' => true,
            ])
            ->add('email', EmailType::class, [
                'label' => 'Email',
                'required' => true,
            ])
            ->add('username', TextType::class, [
                'label' => 'Usuario',
                'required' => true,
            ])
            ->add('password', PasswordType::class, [
                'label' => 'Contraseña',
                'required' => true,
            ])
            ->add('telefono', TextType::class, [
                'label' => 'Teléfono',
                'required' => false,
            ])
            ->add('legajo', TextType::class, [
                'label' => 'Legajo',
                'required' => false,
            ])
            ->add('matricula', TextType::class, [
                'label' => 'Matrícula',
                'required' => false,
            ])
            ->add('fechaNacimiento', DateType::class, [
                'label' => 'Fecha de Nacimiento',
                'required' => false,
                'widget' => 'single_text',
            ])
            ->add('direccion', TextType::class, [
                'label' => 'Dirección',
                'required' => false,
            ])
            ->add('habilitado', CheckboxType::class, [
                'label' => 'Habilitado',
                'required' => false,
            ])
            ->add('roleEntities', EntityType::class, [
                'class' => Role::class,
                'choice_label' => 'displayName',
                'multiple' => true,
                'expanded' => true,
                'label' => 'Roles',
                'help' => 'Selecciona los roles que tendrá este enfermero',
                'required' => false,
                'mapped' => false, // IMPORTANT: This field is now unmapped
                'query_builder' => function ($repository) {
                    return $repository->createQueryBuilder('r')
                        ->where('r.isActive = :active')
                        ->setParameter('active', true)
                        ->orderBy('r.displayName', 'ASC');
                },
                'attr' => [
                    'class' => 'roles-checkboxes'
                ]
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Nurse::class,
        ]);
    }
}
