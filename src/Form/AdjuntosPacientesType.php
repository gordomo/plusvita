<?php

namespace App\Form;

use App\Entity\AdjuntosPacientes;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\File;

class AdjuntosPacientesType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('id_paciente', HiddenType::class)
            ->add('nombre', TextType::class, ['required'=>true])
            ->add('tipo', ChoiceType::class, [
                'label' => 'Motivo de Egreso',
                'choices'  => [
                    'Seleccione una Tipo de Documento Adjunto' => 0,
                    'Documentación' => 1,
                    'Fotos Médicas' => 2,
                    'Administración' => 3,
                    'Informes y Recetas' => 4,
                    'Estudios' => 5,
                    'Otros' => 6,
                ], 'required'=>true])
            ->add('archivoAdjunto', FileType::class, [
                'label' => 'Adjuntar',
                'mapped' => false,
                'required' => true,
                'constraints' => [
                    new File([
                        'maxSize' => '153600K',
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

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => AdjuntosPacientes::class,
        ]);
    }
}
