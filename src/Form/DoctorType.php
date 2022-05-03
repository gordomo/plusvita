<?php

namespace App\Form;

use App\Entity\Doctor;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\ColorType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\File;

class DoctorType extends AbstractType
{
    private $horarios = [
        '08:00' => '08:00',
        '08:30' => '08:30',
        '09:00' => '09:00',
        '09:30' => '09:30',
        '10:00' => '10:00',
        '10:30' => '10:30',
        '11:30' => '11:30',
        '12:00' => '12:00',
        '12:30' => '12:30',
        '13:00' => '13:00',
        '13:30' => '13:30',
        '14:00' => '14:00',
        '14:30' => '14:30',
        '15:00' => '15:00',
        '15:30' => '15:30',
        '16:00' => '16:00',
        '16:30' => '16:30',
        '17:00' => '17:00',
        '17:30' => '17:30',
        '18:00' => '18:00',
        '18:30' => '18:30',
        '19:00' => '19:00',
        '19:30' => '19:30',
        '20:00' => '20:00',
    ];

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $colors = $options['colors'];

        if(!$options['egreso']) {
            $builder
                ->add('nombre', TextType::class)
                ->add('apellido', TextType::class)
                ->add('fNac', DateType::class, ['widget' => 'single_text', 'required' => false])
                ->add('color', ChoiceType::class, [
                    'choices' => array_combine($colors, $colors),
                    'choice_attr' => function($choice, $key, $value) {
                        // adds a class like attending_yes, attending_no, etc
                        return ['style' => 'background:'.$key];
                    },
                ])
                ->add('dni', TextType::class)
                ->add('telefono', NumberType::class, ['html5' => true])
                ->add('email', EmailType::class)
                ->add('legajo', TextType::class)
                ->add('cbu', TextType::class, ['required' => false])
                ->add('tipo', ChoiceType::class,
                    [
                        'label' => 'Tipo de Contrato',
                        'choices' => ['Seleccione un Tipo de contrato' => 0, 'Empleado' => 1, 'Contrato Directo' => 2, 'Contrato por Prestación' => 3, 'Prestación Directa' => 4],
                    ])
                ->add('inicioContrato', DateType::class, ['widget' => 'single_text'])
                ->add('vtoContrato', DateType::class, ['widget' => 'single_text', 'required' => false])
                ->add('vtoMatricula', DateType::class, ['widget' => 'single_text', 'required' => false])
                ->add('libretaSanitaria', NumberType::class, ['html5' => true, 'required' => false])
                ->add('vtoLibretaSanitaria', DateType::class, ['widget' => 'single_text', 'required' => false])
                ->add('emisionLibretaSanitaria', DateType::class, ['widget' => 'single_text', 'required' => false])
                ->add('firmaPdf', FileType::class, [
                    'label' => 'Firma Digital (PDF file)',
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
                            'mimeTypesMessage' => 'Solo archivos con formato PDF son permitidos',
                        ])
                    ],
                ])
                ->add('matricula', TextType::class, ['required' => false])
                ->add('max_cli_turno', NumberType::class, ['html5' => true, 'required' => false])
                ->add('lunesdesde', ChoiceType::class, [
                    'required' => false,
                    'mapped' => false,
                    'choice_attr' => function($choice, $key, $value) {
                        if ($value == '08:00') {
                            return ['selected' => 'selected'];
                        } else {
                            return [];
                        }
                    },
                    'placeholder' => 'Desde',
                    'choices' => $this->horarios,
                ])
                ->add('luneshasta', ChoiceType::class, [
                    'required' => false,
                    'mapped' => false,
                    'placeholder' => 'Hasta',
                    'choice_attr' => function($choice, $key, $value) {
                        if ($value == '18:00') {
                            return ['selected' => 'selected'];
                        } else {
                            return [];
                        }
                    },
                    'choices' => $this->horarios,
                ])
                ->add('ylunesdesde', ChoiceType::class, [
                    'required' => false,
                    'mapped' => false,
                    'placeholder' => 'seleccione horario cortado',
                    'choices' => $this->horarios,
                ])
                ->add('yluneshasta', ChoiceType::class, [
                    'required' => false,
                    'mapped' => false,
                    'placeholder' => 'seleccione horario cortado',
                    'choices' => $this->horarios,
                ])

                ->add('martesdesde', ChoiceType::class, [
                    'required' => false,
                    'mapped' => false,
                    'choice_attr' => function($choice, $key, $value) {
                        if ($value == '08:00') {
                            return ['selected' => 'selected'];
                        } else {
                            return [];
                        }
                    },
                    'placeholder' => 'Desde',
                    'choices' => $this->horarios,
                ])
                ->add('marteshasta', ChoiceType::class, [
                    'required' => false,
                    'mapped' => false,
                    'placeholder' => 'Hasta',
                    'choice_attr' => function($choice, $key, $value) {
                        if ($value == '18:00') {
                            return ['selected' => 'selected'];
                        } else {
                            return [];
                        }
                    },
                    'choices' => $this->horarios,
                ])
                ->add('ymartesdesde', ChoiceType::class, [
                    'required' => false,
                    'mapped' => false,
                    'placeholder' => 'seleccione horario cortado',
                    'choices' => $this->horarios,
                ])
                ->add('ymarteshasta', ChoiceType::class, [
                    'required' => false,
                    'mapped' => false,
                    'placeholder' => 'seleccione horario cortado',
                    'choices' => $this->horarios,
                ])

                ->add('miercolesdesde', ChoiceType::class, [
                    'required' => false,
                    'mapped' => false,
                    'choice_attr' => function($choice, $key, $value) {
                        if ($value == '08:00') {
                            return ['selected' => 'selected'];
                        } else {
                            return [];
                        }
                    },
                    'placeholder' => 'Desde',
                    'choices' => $this->horarios,
                ])
                ->add('miercoleshasta', ChoiceType::class, [
                    'required' => false,
                    'mapped' => false,
                    'placeholder' => 'Hasta',
                    'choice_attr' => function($choice, $key, $value) {
                        if ($value == '18:00') {
                            return ['selected' => 'selected'];
                        } else {
                            return [];
                        }
                    },
                    'choices' => $this->horarios,
                ])
                ->add('ymiercolesdesde', ChoiceType::class, [
                    'required' => false,
                    'mapped' => false,
                    'placeholder' => 'seleccione horario cortado',
                    'choices' => $this->horarios,
                ])
                ->add('ymiercoleshasta', ChoiceType::class, [
                    'required' => false,
                    'mapped' => false,
                    'placeholder' => 'seleccione horario cortado',
                    'choices' => $this->horarios,
                ])

                ->add('juevesdesde', ChoiceType::class, [
                    'required' => false,
                    'mapped' => false,
                    'choice_attr' => function($choice, $key, $value) {
                        if ($value == '08:00') {
                            return ['selected' => 'selected'];
                        } else {
                            return [];
                        }
                    },
                    'placeholder' => 'Desde',
                    'choices' => $this->horarios,
                ])
                ->add('jueveshasta', ChoiceType::class, [
                    'required' => false,
                    'mapped' => false,
                    'placeholder' => 'Hasta',
                    'choice_attr' => function($choice, $key, $value) {
                        if ($value == '18:00') {
                            return ['selected' => 'selected'];
                        } else {
                            return [];
                        }
                    },
                    'choices' => $this->horarios,
                ])
                ->add('yjuevesdesde', ChoiceType::class, [
                    'required' => false,
                    'mapped' => false,
                    'placeholder' => 'seleccione horario cortado',
                    'choices' => $this->horarios,
                ])
                ->add('yjueveshasta', ChoiceType::class, [
                    'required' => false,
                    'mapped' => false,
                    'placeholder' => 'seleccione horario cortado',
                    'choices' => $this->horarios,
                ])

                ->add('viernesdesde', ChoiceType::class, [
                    'required' => false,
                    'mapped' => false,
                    'choice_attr' => function($choice, $key, $value) {
                        if ($value == '08:00') {
                            return ['selected' => 'selected'];
                        } else {
                            return [];
                        }
                    },
                    'placeholder' => 'Desde',
                    'choices' => $this->horarios,
                ])
                ->add('vierneshasta', ChoiceType::class, [
                    'required' => false,
                    'mapped' => false,
                    'placeholder' => 'Hasta',
                    'choice_attr' => function($choice, $key, $value) {
                        if ($value == '18:00') {
                            return ['selected' => 'selected'];
                        } else {
                            return [];
                        }
                    },
                    'choices' => $this->horarios,
                ])
                ->add('yviernesdesde', ChoiceType::class, [
                    'required' => false,
                    'mapped' => false,
                    'placeholder' => 'seleccione horario cortado',
                    'choices' => $this->horarios,
                ])
                ->add('yvierneshasta', ChoiceType::class, [
                    'required' => false,
                    'mapped' => false,
                    'placeholder' => 'seleccione horario cortado',
                    'choices' => $this->horarios,
                ])

                ->add('sabadodesde', ChoiceType::class, [
                    'required' => false,
                    'mapped' => false,
                    'choice_attr' => function($choice, $key, $value) {
                        if ($value == '08:00') {
                            return ['selected' => 'selected'];
                        } else {
                            return [];
                        }
                    },
                    'placeholder' => 'Desde',
                    'choices' => $this->horarios,
                ])
                ->add('sabadohasta', ChoiceType::class, [
                    'required' => false,
                    'mapped' => false,
                    'placeholder' => 'Hasta',
                    'choice_attr' => function($choice, $key, $value) {
                        if ($value == '18:00') {
                            return ['selected' => 'selected'];
                        } else {
                            return [];
                        }
                    },
                    'choices' => $this->horarios,
                ])
                ->add('ysabadodesde', ChoiceType::class, [
                'required' => false,
                'mapped' => false,
                'placeholder' => 'seleccione horario cortado',
                'choices' => $this->horarios,
                ])
                ->add('ysabadohasta', ChoiceType::class, [
                    'required' => false,
                    'mapped' => false,
                    'placeholder' => 'seleccione horario cortado',
                    'choices' => $this->horarios,
                ])
                ->add('domingodesde', ChoiceType::class, [
                    'required' => false,
                    'mapped' => false,
                    'choice_attr' => function($choice, $key, $value) {
                        if ($value == '08:00') {
                            return ['selected' => 'selected'];
                        } else {
                            return [];
                        }
                    },
                    'placeholder' => 'Desde',
                    'choices' => $this->horarios,
                ])
                    ->add('domingohasta', ChoiceType::class, [
                        'required' => false,
                        'mapped' => false,
                        'placeholder' => 'Hasta',
                        'choice_attr' => function($choice, $key, $value) {
                            if ($value == '18:00') {
                                return ['selected' => 'selected'];
                            } else {
                                return [];
                            }
                        },
                        'choices' => $this->horarios,
                    ])
                    ->add('ydomingodesde', ChoiceType::class, [
                        'required' => false,
                        'mapped' => false,
                        'placeholder' => 'seleccione horario cortado',
                        'choices' => $this->horarios,
                    ])
                    ->add('ydomingohasta', ChoiceType::class, [
                        'required' => false,
                        'mapped' => false,
                        'placeholder' => 'seleccione horario cortado',
                        'choices' => $this->horarios,
                    ]);


            $builder->get('tipo')->addEventListener(FormEvents::POST_SUBMIT, function (FormEvent $event) {
                $form = $event->getForm();
                $tipo = empty($form->getData()) ? null : $form->getData();
                $this->setupModalidad($form->getParent(), $tipo);
            });

            $builder->addEventListener(FormEvents::PRE_SET_DATA, function (FormEvent $event) {
                $data = $event->getData();
                if (!$data) {
                    return;
                }
                $tipo = empty($data->getTipo()) ? null : $data->getTipo();
                $this->setupModalidad(
                    $event->getForm(),
                    $tipo
                );
            });
        }

        //campos a mostrar para nuevo staff
        if($options['is_new']) {
            $builder
                ->add('password', PasswordType::class);
        }
        //campos a mostrar para staff existente
        elseif($options['egreso']) {
            $builder
                ->add('fechaBaja', DateType::class, [ 'widget' => 'single_text', 'required' => false, 'attr' => ['class' => 'js-datepicker']])
                ->add('motivoBaja', ChoiceType::class,
                    [
                        'label' => 'Motivo de la Baja',
                        'placeholder' => 'Seleccione un Motivo',
                        'choices' => ['Fianlizacion de contrato' => 1, 'Despido' => 2, 'Renuncia' => 3, 'Abanadono' => 3],
                        'required' => false
                    ])
                ->add('concepto', TextType::class, ['required' => false])
                ->add('posicionEnArchivo', NumberType::class, ['required' => false]);
        }


        $builder->add('save', SubmitType::class, ['label' => 'Guardar']);


    }

    private function getModalidades(int $contrato)
    {
        $empleado = [
            'Mucamo/a',
            'Enfermero/a',
            'Auxiliar de enfermeria',
            'Asistente de enfermeria',
            'Mantenimiento',
            'Cocinero',
            'Ayudante de cocina',
            'Administrativo',
            'Recepcionista',
            'Coordinador de pisos',
            'Coordinador general',
            'Coordinador de enfermeria'
        ];
        $directo = [
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
            'Programador',
        ];
        $prestacion = [
            'Profesional por prestacion',
            'Medico Clínico',
            'Medico de guardia',
            'Kinesiologo motora',
            'Kinesiología respiratoria',
            'HidroTerapia motora',
            'Terapista ocupacional',
            'Fonoaudiologo',
            'Psicologo',
            'Fisiatra',
            'Neurologo',
            'Cardiologo',
            'Urologo',
            'Hematologo',
            'Neumonologo',
        ];
        $sinContrato = [
            'Cirujano',
            'Traumatologo',
            'Neumonologo',
        ];

        $modalidades = [
            1 => array_combine($empleado, $empleado),
            2 => array_combine($directo, $directo),
            3 => array_combine($prestacion,$prestacion),
            4 => array_combine($sinContrato, $sinContrato)
        ];
        return $modalidades[$contrato];
    }

    private function setupModalidad(FormInterface $form, ?int $tipo) {
        if (null === $tipo) {
            $form->remove('modalidad');
            return;
        }

        $choices = $this->getModalidades($tipo);
        if (null === $choices) {
            $form->remove('modalidad');
            return;
        }

        $form->add('modalidad', ChoiceType::class,
            [
                'label' => 'Categoria',
                'placeholder' => 'Seleccione una Categoria',
                'choices' => $choices,
                'multiple'=>true,
                'expanded' => true,
                'choice_attr' => function($choice, $key, $value) {
                    // adds a class like attending_yes, attending_no, etc
                    return ['class' => 'attending_'.strtolower($key)];
                },
            ]);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Doctor::class,
            'is_new' => true,
            'egreso' => false,
            'colors' => [],
        ]);
    }
}
