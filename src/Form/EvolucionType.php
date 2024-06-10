<?php

namespace App\Form;

use App\Entity\Evolucion;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\All;
use Symfony\Component\Validator\Constraints\Composite;
use Symfony\Component\Validator\Constraints\File;

class EvolucionType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $modalidad = $options['modalidad'];
        $doctores = $options['doctores'];
        $usuarioActual = $options['usuarioActual'];
        $puedenEditarEvoluciones = $options['puedenEditarEvoluciones'];
        $today = new \DateTime();

        $builder
            ->add('tipo', ChoiceType::class, [
                'required' => true,
                'choices' => $this->getTipos(),
                'choice_attr' => function($choice, $key, $value) use ($modalidad) {
                    if ($value == $modalidad) {
                            return ['selected' => 'selected'];
                        } else {
                            return [];
                        }
                    },
                ]);
            if($puedenEditarEvoluciones) {
                $builder->add('doctor', ChoiceType::class, [
                    'required' => true,
                    'choices' => $doctores,
                    'mapped' => false,
                    'choice_attr' => function($choice, $key, $value) use ($usuarioActual) {
                        if ($value == $usuarioActual) {
                            return ['selected' => 'selected'];
                        } else {
                            return [];
                        }
                    },
                ]);
            }  
            $builder->add('description', TextareaType::class, [
                'attr' => ['style' => 'min-height:12rem', 'class' => 'ckeditor'],
                'required' => true,
            ])
            ->add('fecha', DateType::class, ['label' => 'Fecha', 'required' => true, 'widget' => 'single_text', 'html5' => true, 'attr' => ["max" => $today->format('Y-m-d')]])
            ->add('adjunto', FileType::class, [
                'data_class'=>null,
                'label' => 'Adjunto (PDF)',
                'multiple' => true,
                // unmapped means that this field is not associated to any entity property
                'mapped' => false,

                // make it optional so you don't have to re-upload the PDF file
                // every time you edit the Product details
                'required' => false,

                // unmapped fields can't define their validation using annotations
                // in the associated entity, so you can use the PHP constraint classes
                'constraints' => [
                    new All([
                        'constraints' => [
                            new File([
                                'maxSize' => '1024M',
                                'mimeTypesMessage' => 'Formato de archivo no soportado',
                                'mimeTypes' => [
                                    'application/pdf',
                                    'application/x-pdf',
                                    'image/*',
                                ]
                            ]),
                        ],
                    ]),
                ],
            ])
            ->add('save', SubmitType::class, ['label' => 'Guardar', 'attr' => ['class' => 'btn-success']]);
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Evolucion::class,
            'modalidad' => '',
            'doctores' => '',
            'usuarioActual' => '',
            'puedenEditarEvoluciones' => '',
        ]);
    }

    private function getTipos()
    {
        $tipos = [
            'Seleccione una Opción',
            'Nutricionista',
            'Director medico',
            'Sub director medico',
            'Trabajadora social',
            'Psiquiatra',
            'Infectologo',
            'Contador',
            'Abogado',
            'Estudio contable',
            'Directivo',
            'Profesional por prestacion',
            'Medico de guardia',
            'Medico Clínico',
            'HidroTerapia motora',
            'Kinesiologo',
            'Kinesiologo respiratorio',
            'Terapista ocupacional',
            'Fonoaudiologo',
            'Psicologo',
            'Fisiatra',
            'Neurologo',
            'Cardiologo',
            'Urologo',
            'Hematologo',
            'Neumonologo',
            'Cirujano',
            'Traumatologo',
            'Neumonologo',
        ];

        return array_combine($tipos, $tipos);

    }
}
