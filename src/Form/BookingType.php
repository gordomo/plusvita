<?php

namespace App\Form;

use App\Entity\Booking;
use App\Entity\Cliente;
use App\Entity\Doctor;
use App\Entity\User;
use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Choice;

class BookingType extends AbstractType
{
    public $pctr = '';
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $this->pctr = $options['ctr'];
        $this->isNew = $options['isNew'];
        $doctorEdit = $options['doctor_edit'] ?? false;

        $builder
            ->add('beginAt', DateTimeType::class, [
                'label' => 'Hora de Inicio', 
                'required' => true, 
                'widget' => 'single_text', 
                'html5' => true,
                'input' => 'datetime',
                'attr' => ['class' => 'form-control']
            ])
            ->add('endAt', DateTimeType::class, [
                'label' => 'Hora de Fin', 
                'required' => true, 
                'widget' => 'single_text', 
                'html5' => true,
                'input' => 'datetime',
                'attr' => ['class' => 'form-control']
            ]);

        // Si es un doctor editando, solo mostrar fecha/hora (no mostrar otros campos)
        if (!$doctorEdit) {
            $builder
                ->add('title', TextType::class, ['label' => 'Titulo'])
                ->add('doctor', EntityType::class, [
                    'class' => User::class,
                    'choice_label' => 'NombreApellido',
                    'choice_value' => function(?User $user) {
                        return $user ? $user->getId() : '';
                    },
                    'label' => 'Profesional',
                    'attr' => ['class' => 'predictivo'],
                    'query_builder' => function (EntityRepository $er) {
                        $qb = $er->createQueryBuilder('u')->where("JSON_CONTAINS (u.modalidad, '\"$this->pctr\"', '$') = 1");
                        if ( $this->pctr != '' ) {
                            return $qb;
                        } else {
                            return $er->createQueryBuilder('u')->where("1 = 1");
                        }
                    },
                ])
                ->add('cliente', EntityType::class, [
                    'class' => Cliente::class,
                    'choice_label' => 'NombreApellido',
                    'label' => 'Paciente',
                    'attr' => ['class' => 'predictivo'],
                    'query_builder' => function (EntityRepository $er) {
                        return $er->createQueryBuilder('c')
                            ->andWhere('c.fEgreso > :val')->setParameter('val', new \DateTime())
                            ->orWhere('c.fEgreso IS NULL')
                            ->orderBy('c.nombre', 'ASC')
                        ;
                    },
                ]);
        }

        if($this->isNew && !$doctorEdit) {
            $builder->add('dias', ChoiceType::class, ['required' => false, 'choices'  => [
                'Lunes' => 1,
                'Martes' => 2,
                'Miercoles' => 3,
                'Jueves' => 4,
                'Viernes' => 5,
                'Sábado' => 6,
                'Domingo' => 7,
            ],
                'multiple'=>true,
                'expanded'=>true,
            ])
                ->add('desde', DateType::class, ['label' => 'Desde', 'required' => false, 'widget' => 'single_text', 'html5' => true])
                ->add('hasta', DateType::class, ['label' => 'Hasta', 'required' => false, 'widget' => 'single_text', 'html5' => true]);
        }
        
        $builder
            ->add('save', SubmitType::class, ['label' => 'Guardar', 'attr' => ['class' => 'btn-success']])
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Booking::class,
            'ctr' => '',
            'isNew' => false,
            'doctor_edit' => false,
        ]);
    }
}
