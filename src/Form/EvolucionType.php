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
                ])
            ->add('description', TextareaType::class, [
                'attr' => ['style' => 'min-height:12rem']
            ])
            ->add('fecha', DateType::class, ['label' => 'Fecha', 'required' => true, 'widget' => 'single_text', 'html5' => true, 'attr' => ['class' => 'js-datepicker', "max" => $today->format('Y-m-d')]])
            ->add('adjunto', FileType::class, [
                'label' => 'Adjuntar',
                'mapped' => false,
                'required' => false,
                'constraints' => [
                    new File([
                        'maxSize' => '1024k',
                        'mimeTypes' => [
                            'application/pdf',
                            'application/x-pdf',
                            'image/*',
                        ],
                        'mimeTypesMessage' => 'Solo archivos con formato PDF son permitidos',
                    ])
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
