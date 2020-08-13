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
        $builder
            ->add('nombre', TextType::class)
            ->add('apellido', TextType::class)
            ->add('dni', NumberType::class, ['html5' => true, 'label' => 'Número de Documento'])
            ->add('email', EmailType::class, ['required'=>false,])
            ->add('telefono', TextType::class, ['label' => 'Teléfono', 'required'=>false,])
            ->add('fNacimiento', DateType::class, ['label' => 'Fecha de Nacimiento', 'required'=>false, 'widget' => 'single_text', 'html5' => true, 'attr' => ['class' => 'js-datepicker'],])
            ->add('hClinica', TextType::class, ['label' => 'Número de Historia Clínica', 'required'=>false,])
            ->add('obraSocial', ChoiceType::class, [
                'label' => 'Obra Social',
                'placeholder' => 'Seleccione una Obra Social',
                'choices'  => [
                    'Swiss medical' => "1",
                    'OSDE' => "2",
                    'Otra' => "3",
                ],
            ])
            ->add('fIngreso', DateType::class, ['label' => 'Fecha de Ingreso', 'required'=>false, 'widget' => 'single_text', 'attr' => ['class' => 'js-datepicker']])
            ->add('modalidad', ChoiceType::class, [
                'label' => 'Modalidad',
                'placeholder' => 'Seleccione una modalidad',
                'choices'  => [
                    'Ambulatorio' => "1",
                    'Internacion' => "2",
                    'Hospital de día' => "3",
                    'ART' => "4",
                ],
                'multiple'=>false,
                'expanded'=>false,
            ])
            ->add('motivoIng', ChoiceType::class, [
                'label' => 'Patología de Ingreso',
                'choices'  => [
                    'Seleccione una Patología' => 0,
                    'Neurologicas' => 1,
                    'Traumatológicas' => 2,
                    'Respiratorias' => 3,
                    'Paliativos' => 4,
                    'Patologías laborales' => 5,
                ],
                'multiple'=>false,
                'expanded'=>false,
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
                'expanded'=>true,
                'label' => 'Profesionales (el primer seleccionado será considerado referente)',
            ])
            ->add('habitacion', TextType::class, ['label' => 'Habitación', 'required'=>false])
            ->add('nCama', TextType::class, ['label' => 'Numero de Cama', 'required'=>false])
            ->add('familiarResponsableNombre', TextType::class, ['label' => 'Nombre', 'required'=>false])
            ->add('familiarResponsableTel', TextType::class, ['label' => 'Teléfono', 'required'=>false])
            ->add('familiarResponsableMail', TextType::class, ['label' => 'EMail', 'required'=>false])
            ->add('vinculoResponsable', TextType::class, ['label' => 'Vinculo', 'required'=>false])
            ->add('vieneDe', TextType::class, ['label' => 'Nombre', 'required'=>false])
            ->add('docDerivante', TextType::class, ['label' => 'Profesional Derivante', 'required'=>false])
            ->add('edad', TextType::class, ['label' => 'Edad', 'required'=>false])
            ->add("familiarResponsableExtra", HiddenType::class, array("mapped"=>false, "label"=>false))
            ->add('save', SubmitType::class, ['label' => 'Guardar', 'attr' => ['class' => 'btn-success']]);

//motivoEgr
        if(!$options['is_new']) {
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
                ]);


        }

        $builder->addEventListener(FormEvents::PRE_SUBMIT,function (FormEvent $event) {
            $form = $event->getForm();

            dd($form->get('familiarResponsableExtra')->getViewData());


        });


        $builder->get('motivoIng')->addEventListener(FormEvents::POST_SUBMIT, function(FormEvent $event) {
                $form = $event->getForm();
                $motivoIng = empty($form->getData()) ? null : $form->getData();
                $this->setupMotivoIngEsp($form->getParent(), $motivoIng);
            }
        );

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
            'pop', 'acv', 'izquemico', 'hemorragico', 'tec', 'em', 'ela', 'guillain', 'barre', 'trauma', 'medular', 'otras'
        ];
        $traumatologicas = [
            'pop', 'politrauma', 'amputaciones', 'otras'
        ];
        $respiratorio = [
            'rehabilitacion', 'respiratoria', 'pop'
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
            'is_new' => true
        ]);
    }
}
