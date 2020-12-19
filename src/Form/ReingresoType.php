<?php

namespace App\Form;

use App\Entity\Cliente;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ReingresoType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {

        $habitaciones = $options['habitaciones'] ?? '';



            $builder
                ->add('motivoReingresoDerivacion', TextType::class)
                ->add('fechaReingresoDerivacion', DateType::class, ['label' => 'Fecha de Reingreso', 'required' => false, 'widget' => 'single_text', 'html5' => true, 'attr' => ['class' => 'js-datepicker'],])
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
                ]);

                if ($options['camasDisp']) {
                    $builder->add('nCama', ChoiceType::class, [
                        'label' => 'Cama',
                        'choices'  => $options['camasDisp']
                    ]);
                }

        $builder->add('save', SubmitType::class, ['label' => 'Guardar', 'attr' => ['class' => 'btn-success']]);


    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Cliente::class,
            'habitaciones' => [],
            'camasDisp' => 0,
        ]);
    }
}
