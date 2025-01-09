<?php

namespace App\Form;

use App\Entity\Item;
use App\Entity\TipoItem;
use App\Entity\Ubicacion;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ItemType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('nombre', TextType::class)
            ->add('tipo', TextType::class)
            ->add('cantidad', IntegerType::class)
            ->add('tipo', EntityType::class, [
                'class' => TipoItem::class,
                'choice_label' => 'nombre', 
            ])
            ->add('ubicacion_actual', EntityType::class, [
                'class' => Ubicacion::class,
                'choice_label' => 'nombre',
                'placeholder' => 'Selecciona una ubicación'
            ]);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Item::class,
        ]);
    }
}

