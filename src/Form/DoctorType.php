<?php

namespace App\Form;

use App\Entity\Doctor;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
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
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('nombre', TextType::class)
            ->add('apellido', TextType::class)
            ->add('dni', TextType::class)
            ->add('telefono', NumberType::class, ['html5' => true])
            ->add('email', EmailType::class)
            ->add('legajo', NumberType::class)
            ->add('posicionEnArchivo', NumberType::class)
            ->add('tipo', ChoiceType::class,
                [
                    'label' => 'Tipo de Contrato',
                    'choices' => ['Seleccione un Tipo de contrato' => 0, 'Empleado' => 1, 'Contrato Directo' => 2, 'Contrato por Prestación' => 3, 'Prestación Directa' => 4],
                ])
            ->add('inicioContrato', DateType::class, [ 'widget' => 'single_text'])
            ->add('vtoContrato', DateType::class, [ 'widget' => 'single_text', 'required' => false] )
            ->add('vtoMatricula', DateType::class, [ 'widget' => 'single_text', 'required' => false] )

            ->add('libretaSanitaria', NumberType::class, [ 'html5' => true, 'required' => false])
            ->add('vtoLibretaSanitaria', DateType::class, [ 'widget' => 'single_text', 'required' => false] )
            ->add('emisionLibretaSanitaria', DateType::class, [ 'widget' => 'single_text', 'required' => false] )

            ->add('firmaPdf', FileType::class, [
                'label' => 'Firma Digital (PDF file)',
                'mapped' => false,
                'required' => false,
                'constraints' => [
                    new File([
                        'maxSize' => '1024k',
                        'mimeTypes' => [
                            'application/pdf',
                            'application/x-pdf',
                        ],
                        'mimeTypesMessage' => 'Solo archivos con formato PDF son permitidos',
                    ])
                ],
            ])
            ->add('matricula', TextType::class, ['required' => false]);
        if($options['is_new']) {
            $builder
                ->add('password', PasswordType::class);
        } else {
            $builder
            ->add('fechaBaja', DateType::class, [ 'widget' => 'single_text', 'required' => false])
                ->add('motivoBaja', ChoiceType::class,
                    [
                        'label' => 'Motivo de la Baja',
                        'placeholder' => 'Seleccione un Motivo',
                        'choices' => ['Fianlizacion de contrato' => 1, 'Despido' => 2, 'Renuncia' => 3, 'Abanadono' => 3],
                        'required' => false
                    ])
                ->add('concepto', TextType::class, ['required' => false]);
        }
            $builder
            ->add('save', SubmitType::class, ['label' => 'Guardar'])
        ;

        $builder->get('tipo')->addEventListener(FormEvents::POST_SUBMIT, function(FormEvent $event) {
                $form = $event->getForm();
                $tipo = empty($form->getData()) ? null : $form->getData();
                $this->setupModalidad($form->getParent(), $tipo);
            }
        );

        $builder->addEventListener(FormEvents::PRE_SET_DATA,function (FormEvent $event) {
                $data = $event->getData();
                if (!$data) {
                    return;
                }
                $tipo = empty($data->getTipo()) ? null : $data->getTipo();
                $this->setupModalidad(
                    $event->getForm(),
                    $tipo
                );
            }
        );
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Doctor::class,
            'is_new' => true
        ]);
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
        ];
        $prestacion = [
            'Profesional por prestacion',
            'Medico de guardia',
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
}
