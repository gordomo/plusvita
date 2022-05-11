<?php

namespace App\Form;


use App\Entity\HistoriaIngreso;
use Symfony\Component\Validator\Constraints\All;
use Symfony\Component\Validator\Constraints\Composite;
use Symfony\Component\Validator\Constraints\File;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class HistoriaIngresoType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('antecedentesTexto', TextareaType::class)
            ->add('enfermedadActual', TextareaType::class)
            ->add('examenFisicoAlIngreso', TextareaType::class)
            ->add('examenComplementarioDesc', TextareaType::class)
            ->add('adjunto', FileType::class, [
                'data_class'=>null,
                'label' => 'Adjunto (PDF)',
                'multiple' => true,
                // unmapped means that this field is not associated to any entity property
                'mapped' => false,

                // make it optional so you don't have to re-upload the PDF file
                // every time you edit the Product details
                'required' => false,

            ])
            ->add('indicaciones', TextareaType::class)
            ->add('save', SubmitType::class, ['label' => 'Guardar'])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => HistoriaIngreso::class,
        ]);
    }
}
