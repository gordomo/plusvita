<?php

namespace App\Form;

use App\Entity\Cliente;
use App\Entity\Doctor;
use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ClienteType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $obrasSociales = $options['obrasSociales'] ?? '';
        $habitaciones = $options['habitaciones'] ?? '';

        ksort($obrasSociales);
        if (!$options['egreso']) {
            $builder
                ->add('nombre', TextType::class)
                ->add('apellido', TextType::class)
                ->add('dni', NumberType::class, ['html5' => true, 'label' => 'Número de Documento'])
                ->add('email', EmailType::class, ['required' => false,])
                ->add('telefono', TextType::class, ['label' => 'Teléfono', 'required' => false,])
                ->add('fNacimiento', DateType::class, ['label' => 'Fecha de Nacimiento', 'required' => false, 'widget' => 'single_text', 'html5' => true, 'attr' => ['class' => 'js-datepicker'],])
                ->add('hClinica', TextType::class, ['label' => 'Número de Historia Clínica', 'required' => false,])
                ->add('obraSocial', ChoiceType::class, [
                    'label' => 'Obra Social',
                    'placeholder' => 'Seleccione una Obra Social',
                    'choices' => $obrasSociales
                ])
                ->add('obraSocialTelefono', TextType::class, ['required' => false, 'label' => 'Teléfono'])
                ->add('obraSocialAfiliado', TextType::class, ['required' => false, 'label' => 'N Afiliado'])
                ->add('tipoDePago', ChoiceType::class, [
                    'label' => 'Tipo de Pago',
                    'placeholder' => 'Seleccione un Tipo de Pago',
                    'choices' => [
                        'Particular' => 1,
                        'Reintegro' => 2,
                        'Discapacidad' => 3,
                        'ART' => 4,
                        'Convenio' => 5,
                        'Amparo' => 6,
                        'Presupuesto' => 7,
                    ],
                ])
                ->add('sistemaDeEmergenciaNombre', TextType::class, ['required' => false, 'label' => 'Sistema de emergencias'])
                ->add('sistemaDeEmergenciaTel', TextType::class, ['required' => false, 'label' => 'Teléfono'])
                ->add('sistemaDeEmergenciaAfiliado', TextType::class, ['required' => false, 'label' => 'N Afiliado'])
                ->add('fIngreso', DateType::class, ['label' => 'Fecha de Ingreso', 'required' => false, 'widget' => 'single_text', 'attr' => ['class' => 'js-datepicker']])
                ->add('modalidad', ChoiceType::class, [
                    'label' => 'Modalidad',
                    'placeholder' => 'Seleccione una modalidad',
                    'choices' => [
                        'Ambulatorio' => "1",
                        'Internacion' => "2",
                        'Hospital de día' => "3",
                        'ART' => "4",
                    ],
                    'multiple' => false,
                    'expanded' => false,
                ])
                ->add('motivoIng', ChoiceType::class, [
                    'label' => 'Patología de Ingreso',
                    'choices' => [
                        'Seleccione una Patología' => 0,
                        'Neurologicas' => 1,
                        'Traumatológicas' => 2,
                        'Respiratorias' => 3,
                        'Paliativos' => 4,
                        'Patologías laborales' => 5,
                    ],
                    'multiple' => false,
                    'expanded' => false,
                ])
                ->add('docReferente', EntityType::class, [
                    'class' => Doctor::class,
                    'choice_label' => 'NombreApellido',
                    'query_builder' => function (EntityRepository $er) {
                        return $er->createQueryBuilder('u')
                            ->where("JSON_CONTAINS (u.modalidad, '\"Fisiatra\"', '$') = 1")
                            ->orWhere("JSON_CONTAINS (u.modalidad, '\"Director medico\"', '$') = 1")
                            ->orWhere("JSON_CONTAINS (u.modalidad, '\"Sub director medico\"', '$') = 1");
                    },


                    'multiple' => true,
                    'expanded' => true,
                    'label' => 'Profesionales Referentes',
                ])
                ->add('disponibleParaTerapia', ChoiceType::class, [
                    'required' => true,
                    'label' => 'Disponible Para Terapia',
                    'multiple' => false,
                    'expanded' => true,
                    'choices' => [
                        'Si' => true,
                        'No' => false,
                        ],
                    ])
                ->add('habitacion', ChoiceType::class, [
                    'required' => false,
                    'label' => "Habitación",
                    'placeholder' => "Seleccione una Habitación",
                    'choice_attr' => function($choice, $key, $value) {
                        // adds a class like attending_yes, attending_no, etc
                        return ['class' => 'attending_'.strtolower($key)];
                    },
                    'choices' => $habitaciones
                ])

                //->add('nCama', TextType::class, ['label' => 'Numero de Cama', 'required' => false])
                ->add('familiarResponsableNombre', TextType::class, ['label' => 'Nombre', 'required' => false])
                ->add('familiarResponsableTel', TextType::class, ['label' => 'Teléfono', 'required' => false])
                ->add('familiarResponsableMail', TextType::class, ['label' => 'EMail', 'required' => false])
                ->add('vinculoResponsable', TextType::class, ['label' => 'Vinculo', 'required' => false])
                ->add('familiarResponsableAcompanante', ChoiceType::class, [
                    'choices' => [
                        'Si' => true,
                        'No' => false,
                    ],
                    'multiple' => false,
                    'expanded' => false,
                    'label' => 'Es acompañante?',
                ])
                ->add('vieneDe', TextType::class, ['label' => 'Nombre', 'required' => false])
                ->add('docDerivante', TextType::class, ['label' => 'Profesional Derivante', 'required' => false])
                ->add('edad', TextType::class, ['label' => 'Edad', 'required' => false])
                ->add('dieta', TextType::class, ['label' => 'Dieta', 'required' => false])
                ->add("familiarResponsableExtra", HiddenType::class, array("mapped" => false, "label" => false));

                $builder->get('motivoIng')->addEventListener(FormEvents::POST_SUBMIT, function(FormEvent $event) {
                    $form = $event->getForm();
                    $motivoIng = empty($form->getData()) ? null : $form->getData();
                    $this->setupMotivoIngEsp($form->getParent(), $motivoIng);
                });

                $builder->addEventListener(FormEvents::PRE_SET_DATA,function (FormEvent $event) {
                    $data = $event->getData();
                    if (!$data) {
                        return;
                    }
                    $motivoIng = empty($data->getMotivoIng()) ? null : $data->getMotivoIng();
                    $this->setupMotivoIngEsp(
                        $event->getForm(),
                        $motivoIng
                    );
                }
                );
            }
            if(!$options['is_new']) {
                $builder->add("no_nuevo", HiddenType::class, array("mapped" => false, "label" => false));
            }
            if($options['egreso'] || $options['is_new']) {
                $builder
                    ->add('fEgreso', DateType::class, ['label' => 'Fecha de Egreso', 'required'=>false, 'widget' => 'single_text', 'attr' => ['class' => 'js-datepicker']])
                    ->add('motivoEgr', ChoiceType::class, [
                        'label' => 'Motivo de Egreso',
                        'choices'  => [
                            'Seleccione una Motivo' => 0,
                            'Alta médica' => 1,
                            'Alta voluntaria' => 2,
                            'Obito' => 3,
                            'Traslado' => 4,
                            'Tratamiento inconcluso (no renovado)' => 5,
                            'Tratamiento inconcluso (abandonado)' => 6,
                        ],
                        'multiple'=>false,
                        'expanded'=>false,
                    ])
                    ->add('posicionEnArchivo', TextType::class, ['required'=>false, 'label' => 'Posición en Archivo']);
            }

            if ($options['camasDisp']) {
                $builder->add('nCama', ChoiceType::class, [
                    'label' => 'Cama',
                    'choices'  => $options['camasDisp']
                ]);
            }
            if ($options['bloquearHab']) {
                $builder->add('habPrivada', ChoiceType::class, [
                    'label' => 'Habitacion Individual',
                    'choices'  => ['no'=>0, 'si'=>1],
                ]);
            }

            $builder->add('save', SubmitType::class, ['label' => 'Guardar', 'attr' => ['class' => 'btn-success']]);


    }


    private function setupMotivoIngEsp(FormInterface $form, ?int $motivo) {
        if (null === $motivo) {
            $form->remove('motivoIngEspecifico');
            return;
        }

        $choices = $this->getMotivoIngEsp($motivo);
        if (null === $choices) {
            $form->remove('motivoIngEspecifico');
            return;
        }

        $form->add('motivoIngEspecifico', ChoiceType::class,
            [
                'label' => 'Patología de Ingreso Especifica',
                'placeholder' => 'Seleccione una Patología Especifica',
                'choices' => $choices,
                'multiple'=>false,
                'expanded'=>false,
            ]);
    }

    private function getMotivoIngEsp(int $motivo)
    {
        $neurologicas = [
            'pop', 'acv izquemico', 'acv hemorragico', 'tec', 'em', 'ela', 'guillain barre', 'trauma medular', 'otras'
        ];
        $traumatologicas = [
            'pop', 'politrauma', 'amputaciones', 'otras'
        ];
        $respiratorio = [
            'rehabilitacion respiratoria', 'pop'
        ];
        $paliativos = [
            'ca', 'otros'
        ];
        $otros = [
            'otros'
        ];

        $motivoIngEsp = [
            1 => array_combine($neurologicas, $neurologicas),
            2 => array_combine($traumatologicas, $traumatologicas),
            3 => array_combine($respiratorio,$respiratorio),
            4 => array_combine($paliativos, $paliativos),
            5 => array_combine($otros, $otros),
        ];
        return $motivoIngEsp[$motivo];
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Cliente::class,
            'is_new' => true,
            'egreso' => false,
            'obrasSociales' => [],
            'habitaciones' => [],
            'camasDisp' => 0,
            'bloquearHab' => false,
        ]);
    }
}
