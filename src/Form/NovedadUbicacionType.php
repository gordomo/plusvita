<?php

namespace App\Form;

use App\Entity\NovedadUbicacion;
use App\Entity\User;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class NovedadUbicacionType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('titulo', TextType::class, [
                'label' => 'Título de la Novedad',
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Ej: Problema con la iluminación'
                ]
            ])
            ->add('descripcion', TextareaType::class, [
                'label' => 'Descripción',
                'attr' => [
                    'class' => 'form-control',
                    'rows' => 4,
                    'placeholder' => 'Describe detalladamente la novedad...'
                ]
            ])
            ->add('prioridad', ChoiceType::class, [
                'label' => 'Prioridad',
                'choices' => [
                    'Baja' => 'baja',
                    'Media' => 'media',
                    'Alta' => 'alta',
                    'Crítica' => 'critica'
                ],
                'attr' => [
                    'class' => 'form-control'
                ]
            ])
            ->add('estado', ChoiceType::class, [
                'label' => 'Estado',
                'choices' => [
                    'Pendiente' => 'pendiente',
                    'En Proceso' => 'en_proceso',
                    'Resuelto' => 'resuelto',
                    'Cancelado' => 'cancelado'
                ],
                'attr' => [
                    'class' => 'form-control'
                ]
            ])
            ->add('usuario_asignado', EntityType::class, [
                'label' => 'Usuario Asignado',
                'class' => User::class,
                'choice_label' => function(User $user) {
                    return $user->getNombreApellido() ?: $user->getEmail();
                },
                'query_builder' => function(\Doctrine\ORM\EntityRepository $er) {
                    return $er->createQueryBuilder('u')
                        ->where('u.nombre IS NOT NULL')
                        ->andWhere('u.nombre != :empty')
                        ->andWhere('u.apellido IS NOT NULL')
                        ->andWhere('u.apellido != :empty')
                        ->setParameter('empty', '')
                        ->orderBy('u.nombre', 'ASC');
                },
                'required' => false,
                'placeholder' => 'Seleccionar usuario...',
                'attr' => [
                    'class' => 'form-control'
                ]
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => NovedadUbicacion::class,
        ]);
    }
}
