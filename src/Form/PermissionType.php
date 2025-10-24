<?php

namespace App\Form;

use App\Entity\Permission;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class PermissionType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('name', TextType::class, [
                'label' => 'Nombre del Permiso',
                'attr' => [
                    'placeholder' => 'Ej: patient.create',
                    'class' => 'form-control'
                ],
                'help' => 'Formato: categoria.accion (ejemplo: patient.create, inventory.manage)'
            ])
            ->add('displayName', TextType::class, [
                'label' => 'Nombre para Mostrar',
                'attr' => [
                    'placeholder' => 'Ej: Crear Pacientes',
                    'class' => 'form-control'
                ]
            ])
            ->add('description', TextareaType::class, [
                'label' => 'Descripción',
                'required' => false,
                'attr' => [
                    'placeholder' => 'Descripción del permiso...',
                    'class' => 'form-control',
                    'rows' => 3
                ]
            ])
            ->add('category', ChoiceType::class, [
                'label' => 'Categoría',
                'choices' => [
                    'Administración' => 'Administración',
                    'Pacientes' => 'Pacientes',
                    'Agenda' => 'Agenda',
                    'Configuración' => 'Configuración',
                    'Consumibles' => 'Consumibles',
                    'Habitaciones' => 'Habitaciones',
                    'Informes' => 'Informes',
                    'Inventario' => 'Inventario',
                    'Liquidaciones' => 'Liquidaciones',
                    'QR' => 'QR',
                    'Reclamos' => 'Reclamos',
                    'Otro' => 'Otro',
                ],
                'attr' => ['class' => 'form-control'],
                'placeholder' => 'Seleccione una categoría'
            ])
            ->add('isActive', ChoiceType::class, [
                'label' => 'Estado',
                'choices' => [
                    'Activo' => true,
                    'Inactivo' => false,
                ],
                'attr' => ['class' => 'form-control']
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Permission::class,
        ]);
    }
}
