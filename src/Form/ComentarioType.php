<?php

namespace App\Form;

use App\Entity\ComentarioReclamo;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ComentarioType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('texto', TextareaType::class, [
                'label' => 'Comentario',
                'attr' => ['placeholder' => 'Escribe tu comentario aquí...'],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => ComentarioReclamo::class,
        ]);
    }
}
