<?php

namespace App\Form;

use App\Entity\Consumible;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ConsumibleType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('nombre')
            ->add('existencia')
            ->add('precio')
            ->add('unidades', ChoiceType::class, [
            'required' => true,
            'multiple' => false,
            'expanded' => false,
            'choices' => [
                'Unidades' => 'Unidades',
                'Kilos' => 'Kilos',
                'Gramos' => 'Gramos',
                'Bulto' => 'Bulto'
            ],
    ])
            ->add('save', SubmitType::class, ['label' => 'Guardar', 'attr' => ['class' => 'btn-success']]);
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Consumible::class,
        ]);
    }
}
