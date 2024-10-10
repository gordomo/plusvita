<?php

namespace App\Form;

use App\Entity\Reclamo;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Validator\Constraints\File;

class ReclamoType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('area', ChoiceType::class, [
                'label' => 'Área',
                'required' => 'true',
                'placeholder' => 'Seleccione un Área de Reclamo',
                'choices' => [
                    'Médica' => 1,
                    'Enfermería' => 2,
                    'General' => 3,
                ],
            ])
            ->add('adjunto', FileType::class, [
                'label' => 'Imagen',
                'mapped' => false,
                'required' => false,
                'constraints' => [
                    new File([
                        'maxSize' => '5625k',
                        'mimeTypes' => [
                            'application/pdf',
                            'application/x-pdf',
                            'image/*',

                        ],
                        'mimeTypesMessage' => 'Imagenes only',
                    ])
                ],
            ])
            ->add('texto', TextareaType::class, ['label' => 'Descripción',])
            ->add('contacto', EmailType::class, ['label' => 'Email', 'required' => true])
            
            /* ->add('estado', ChoiceType::class, [
                'label' => 'Estado',
                'required' => 'true',
                'placeholder' => 'Seleccione',
                'choices' => [
                    'Iniciado' => 1,
                    'Visto' => 2,
                    'Resuelto' => 3,
                    'Observado' => 4,
                ],
            ]) */

            ->add('save', SubmitType::class, ['label' => 'Enviar', 'attr' => ['class' => 'btn-success']])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Reclamo::class,
        ]);
    }
}
