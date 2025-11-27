<?php

namespace App\Form;

use App\Entity\Role;
use App\Entity\Permission;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class RoleType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('name', TextType::class, [
                'label' => 'Nombre del rol',
                'help' => 'Nombre único del rol (ej: admin, manager, operator)',
                'attr' => [
                    'placeholder' => 'admin'
                ]
            ])
            ->add('displayName', TextType::class, [
                'label' => 'Nombre para mostrar',
                'help' => 'Nombre que se mostrará en la interfaz',
                'attr' => [
                    'placeholder' => 'Administrador'
                ]
            ])
            ->add('description', TextareaType::class, [
                'label' => 'Descripción',
                'required' => false,
                'help' => 'Descripción del rol y sus responsabilidades',
                'attr' => [
                    'rows' => 3,
                    'placeholder' => 'Descripción del rol...'
                ]
            ])
            ->add('category', ChoiceType::class, [
                'label' => 'Categoría',
                'required' => false,
                'placeholder' => 'Seleccione una categoría',
                'choices' => Role::getCategories(),
                'help' => 'Categoría del rol (Médico, Enfermería, Administrativo, etc.)',
                'attr' => [
                    'class' => 'form-control'
                ]
            ])
            ->add('isActive', CheckboxType::class, [
                'label' => 'Activo',
                'help' => 'Si el rol está activo y disponible para asignar',
                'required' => false
            ])
            ->add('permissions', EntityType::class, [
                'class' => Permission::class,
                'choice_label' => 'displayName',
                'multiple' => true,
                'expanded' => true,
                'label' => 'Permisos',
                'help' => 'Selecciona los permisos que tendrá este rol',
                'required' => false,
                'query_builder' => function ($repository) {
                    return $repository->createQueryBuilder('p')
                        ->where('p.isActive = :active')
                        ->setParameter('active', true)
                        ->orderBy('p.category', 'ASC')
                        ->addOrderBy('p.displayName', 'ASC');
                },
                'group_by' => function($permission) {
                    return $permission->getCategory();
                },
                'attr' => [
                    'class' => 'permissions-checkboxes'
                ]
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Role::class,
        ]);
    }
}
