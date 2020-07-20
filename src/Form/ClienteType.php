<?php

namespace App\Form;

use App\Entity\Cliente;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ClienteType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('hClinica')
            ->add('nombre')
            ->add('apellido')
            ->add('dni')
            ->add('email')
            ->add('telefono')
            ->add('fIngreso')
            ->add('fEgreso')
            ->add('motivoIng')
            ->add('motivoEgr')
            ->add('activo')
            ->add('vieneDe')
            ->add('docDerivante')
            ->add('docReferente')
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Cliente::class,
        ]);
    }
}
