<?php

namespace App\Form;

use App\Entity\UserContract;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class UserContractType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('tipo', ChoiceType::class, [
                'label' => 'Tipo de Contrato',
                'choices' => [
                    'Seleccione un tipo' => '0',
                    'Empleado' => '1',
                    'Contrato Directo' => '2',
                    'Contrato por Prestación' => '3',
                    'Prestación Directa' => '4',
                ],
                'attr' => ['class' => 'form-control']
            ])
            ->add('inicioContrato', DateType::class, [
                'label' => 'Fecha de Inicio',
                'widget' => 'single_text',
                'attr' => ['class' => 'form-control']
            ])
            ->add('vtoContrato', DateType::class, [
                'label' => 'Fecha de Vencimiento',
                'widget' => 'single_text',
                'required' => false,
                'help' => 'Dejar vacío para contrato indefinido',
                'attr' => ['class' => 'form-control']
            ])
            ->add('cbu', TextType::class, [
                'label' => 'CBU',
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => '22 dígitos',
                    'maxlength' => 22
                ]
            ])
            ->add('concepto', TextType::class, [
                'label' => 'Concepto',
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Descripción del contrato'
                ]
            ])
            ->add('observaciones', TextareaType::class, [
                'label' => 'Observaciones',
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                    'rows' => 3,
                    'placeholder' => 'Notas adicionales sobre el contrato'
                ]
            ])
            ->add('isActive', CheckboxType::class, [
                'label' => 'Contrato Activo',
                'required' => false,
                'help' => 'Si se marca, este será el contrato activo y desactivará los demás'
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => UserContract::class,
        ]);
    }
}
