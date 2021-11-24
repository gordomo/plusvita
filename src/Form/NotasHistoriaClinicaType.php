<?php

namespace App\Form;

use App\Entity\NotasHistoriaClinica;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class NotasHistoriaClinicaType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('text')
            ->add('fecha')
            ->add('user_id')
            ->add('client_id')
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => NotasHistoriaClinica::class,
        ]);
    }
}
