<?php

namespace App\Form;

use App\Entity\AdjuntosStaff;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\File;

class AdjuntosStaffType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('id_staff', HiddenType::class)
            ->add('nombre', TextType::class, ['required'=>true])
            ->add('tipo', ChoiceType::class, [
                'label' => 'Motivo de Egreso',
                'choices'  => [
                    'Seleccione una Tipo de Documento Adjunto' => 0,
                    'Documentación' => 1,
                    'Novedades' => 2,
                    'Certificados Médicos' => 3,
                    'Evaluaciones' => 4,
                    'Otros' => 5,
                ], 'required'=>true])
            ->add('archivoAdjunto', FileType::class, [
                'label' => 'Adjuntar',
                'mapped' => false,
                'required' => true,
                'constraints' => [
                    new File([
                        'maxSize' => '1024k',
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
            'data_class' => AdjuntosStaff::class,
        ]);
    }
}
