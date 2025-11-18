<?php

namespace App\Form;

use App\Entity\User;
use App\Entity\Role;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
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
            ->add('email', EmailType::class, [
                'label' => 'Email *',
                'required' => true,
                'attr' => ['placeholder' => 'ejemplo@correo.com', 'class' => 'form-control']
            ])
            ->add('password', PasswordType::class, [
                'label' => 'Contraseña *',
                'required' => true,
                'attr' => ['placeholder' => 'Ingrese una contraseña segura', 'class' => 'form-control'],
                'empty_data' => ''
            ])
            ->add('nombre', TextType::class, [
                'label' => 'Nombre',
                'required' => false,
                'attr' => ['placeholder' => 'Nombre del usuario', 'class' => 'form-control']
            ])
            ->add('apellido', TextType::class, [
                'label' => 'Apellido',
                'required' => false,
                'attr' => ['placeholder' => 'Apellido del usuario', 'class' => 'form-control']
            ])
            ->add('dni', TextType::class, [
                'label' => 'DNI',
                'required' => false,
                'attr' => ['placeholder' => 'Documento Nacional de Identidad', 'class' => 'form-control']
            ])
            ->add('telefono', TelType::class, [
                'label' => 'Teléfono',
                'required' => false,
                'attr' => ['placeholder' => 'Número de teléfono', 'class' => 'form-control']
            ])
            ->add('legajo', TextType::class, [
                'label' => 'Legajo',
                'required' => false,
                'attr' => ['placeholder' => 'Número de legajo', 'class' => 'form-control']
            ])
            ->add('roleEntities', EntityType::class, [
                'class' => Role::class,
                'choice_label' => 'displayName',
                'multiple' => true,
                'expanded' => true,
                'label' => 'Roles *',
                'help' => 'Selecciona los roles que tendrá este usuario',
                'required' => true,
                'mapped' => false,
                'query_builder' => function ($repository) {
                    return $repository->createQueryBuilder('r')
                        ->where('r.isActive = :active')
                        ->setParameter('active', true)
                        ->orderBy('r.displayName', 'ASC');
                },
                'attr' => [
                    'class' => 'roles-checkboxes'
                ],
                'choice_attr' => function($choice, $key, $value) {
                    return ['class' => 'form-check-input'];
                }
            ])
            ->add('habilitado', ChoiceType::class, [
                'label' => 'Habilitado',
                'required' => false,
                'choices' => [
                    'Si' => 1,
                    'No' => 0
                ],
                'attr' => ['class' => 'form-control']
            ])
            ->add('completarInfoDoctor', CheckboxType::class, [
                'label' => 'Completar información para doctor',
                'required' => false,
                'mapped' => false,
                'attr' => [
                    'class' => 'form-check-input completar-info-doctor-checkbox',
                    'id' => 'completar_info_doctor'
                ]
            ])
            // Campos de doctor (se mostrarán cuando se marque el checkbox)
            ->add('doctor_matricula', TextType::class, [
                'label' => 'Matrícula',
                'required' => false,
                'mapped' => false,
                'attr' => [
                    'class' => 'form-control'
                ]
            ])
            ->add('doctor_vtoMatricula', DateType::class, [
                'label' => 'Vencimiento Matrícula',
                'required' => false,
                'mapped' => false,
                'widget' => 'single_text',
                'attr' => [
                    'class' => 'form-control'
                ]
            ])
            ->add('doctor_max_cli_turno', IntegerType::class, [
                'label' => 'Máximo Clientes por Turno',
                'required' => false,
                'mapped' => false,
                'attr' => [
                    'class' => 'form-control',
                    'min' => 1
                ]
            ])
            ->add('doctor_color', TextType::class, [
                'label' => 'Color (hex)',
                'required' => false,
                'mapped' => false,
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => '#2196f3'
                ]
            ])
            // Campos de contrato
            ->add('contract_tipo', ChoiceType::class, [
                'label' => 'Tipo de Contrato',
                'required' => false,
                'mapped' => false,
                'choices' => [
                    'Seleccione un tipo' => '0',
                    'Empleado' => '1',
                    'Contrato Directo' => '2',
                    'Contrato por Prestación' => '3',
                    'Prestación Directa' => '4',
                ],
                'attr' => ['class' => 'form-control']
            ])
            ->add('contract_inicioContrato', DateType::class, [
                'label' => 'Fecha de Inicio del Contrato',
                'required' => false,
                'mapped' => false,
                'widget' => 'single_text',
                'attr' => ['class' => 'form-control']
            ])
            ->add('contract_vtoContrato', DateType::class, [
                'label' => 'Fecha de Vencimiento del Contrato',
                'required' => false,
                'mapped' => false,
                'widget' => 'single_text',
                'help' => 'Dejar vacío para contrato indefinido',
                'attr' => ['class' => 'form-control']
            ])
            ->add('save', SubmitType::class, ['label' => 'Guardar', 'attr' => ['class' => 'btn btn-primary']])
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => User::class,
        ]);
    }
}
