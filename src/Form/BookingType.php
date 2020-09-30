<?php

namespace App\Form;

use App\Entity\Booking;
use App\Entity\Cliente;
use App\Entity\Doctor;
use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class BookingType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('beginAt', DateTimeType::class, ['label' => 'Hora de Inicio', 'required' => true, 'widget' => 'single_text', 'html5' => true, 'attr' => ['class' => 'js-datepicker'],])
            ->add('endAt', DateTimeType::class, ['label' => 'Hora de Inicio', 'required' => true, 'widget' => 'single_text', 'html5' => true, 'attr' => ['class' => 'js-datepicker'],])
            ->add('title', TextType::class, ['label' => 'Titulo'])
            ->add('doctor', EntityType::class, [
                'class' => Doctor::class,
                'choice_label' => 'NombreApellido',
                'label' => 'Profesional',
                'attr' => ['class' => 'predictivo']
            ])
            ->add('cliente', EntityType::class, [
                'class' => Cliente::class,
                'choice_label' => 'NombreApellido',
                'label' => 'Paciente',
                'attr' => ['class' => 'predictivo']
            ])
            ->add('save', SubmitType::class, ['label' => 'Guardar', 'attr' => ['class' => 'btn-success']])
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Booking::class,
        ]);
    }
}
