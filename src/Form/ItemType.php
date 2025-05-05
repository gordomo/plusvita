<?php

namespace App\Form;

use App\Entity\Item;
use App\Entity\TipoItem;
use App\Entity\Ubicacion;

use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\File;

class ItemType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('nombre', TextType::class)
            ->add('identificador', TextType::class, [
                'label' => 'Identificador único (opcional)',
                'required' => false,
                'attr' => ['placeholder' => 'Ej: Escritorio #1, Monitor central, etc.']
            ])
            ->add('tipo', EntityType::class, [
                'class' => TipoItem::class,
                'choice_label' => 'nombre', 
            ])
            ->add('imagen', FileType::class, [
                'label' => 'Imagen (JPG, PNG)',
                'mapped' => false,
                'required' => false,
                'constraints' => [
                    new File([
                        'maxSize' => '153600K',
                        'mimeTypes' => [
                            'image/jpeg',
                            'image/png',
                        ],
                        'mimeTypesMessage' => 'Por favor sube una imagen válida (JPG o PNG)',
                    ])
                ],
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

