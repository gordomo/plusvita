<?php

namespace App\Form;

use App\Entity\InformeMensual;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;

class InformeMensualType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('lugar', TextType::class, [
                'label' => 'Lugar',
                'required' => true,
            ])
            ->add('mesCorrespondiente', TextType::class, [
                'label' => 'Informe correspondiente al mes',
                'required' => true,
            ])
            ->add('escaras', TextareaType::class, [
                'label' => 'Escaras',
                'required' => false,
                'attr' => ['rows' => 3],
            ])
            ->add('tipoEscaras', TextType::class, [
                'label' => 'Tipo de escaras',
                'required' => false,
            ])
            ->add('requerimientosEspeciales', TextareaType::class, [
                'label' => 'Requerimientos especiales',
                'required' => false,
                'attr' => ['rows' => 3],
            ])
            ->add('oxigeno', TextType::class, [
                'label' => 'O2',
                'required' => false,
            ])
            ->add('armBpap', TextType::class, [
                'label' => 'ARM/BPAP',
                'required' => false,
            ])
            ->add('traqueo', TextType::class, [
                'label' => 'Traqueo',
                'required' => false,
            ])
            ->add('alimentacion', TextType::class, [
                'label' => 'Alimentación',
                'required' => false,
            ])
            ->add('tipoAlimentacion', TextType::class, [
                'label' => 'Tipo de alimentación',
                'required' => false,
            ])
            ->add('imcMayor40', CheckboxType::class, [
                'label' => 'IMC +40',
                'required' => false,
            ])
            ->add('aislamientoContacto', TextType::class, [
                'label' => 'Aislamiento de contacto',
                'required' => false,
            ])
            ->add('derivacionesSegundoNivel', TextareaType::class, [
                'label' => 'Derivaciones a II nivel',
                'required' => false,
                'attr' => ['rows' => 3],
            ])
            ->add('antecedentes', TextareaType::class, [
                'label' => 'Antecedentes',
                'required' => false,
                'attr' => ['rows' => 5],
            ])
            ->add('estadoActual', TextareaType::class, [
                'label' => 'Estado actual',
                'required' => false,
                'attr' => ['rows' => 5],
            ])
            ->add('pedidoMedico', TextareaType::class, [
                'label' => 'Pedido médico',
                'required' => false,
                'attr' => ['rows' => 5],
            ])
            // Los siguientes campos han sido eliminados:
            // - evolucionMensual
            // - medicacionActual
            // - frecuenciaSesiones
            // - estudiosComplementarios
            // - proximasConsultas
            // - observaciones
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => InformeMensual::class,
        ]);
    }
}
