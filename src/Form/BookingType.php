<?php

namespace App\Form;

use App\Entity\Booking;
use App\Entity\Cliente;
use App\Entity\Doctor;
use App\Entity\Role;
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
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
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
            // Campo de filtro por rol (no mapeado, solo para filtrar)
            // Los roles se obtienen directamente de la base de datos y se agrupan por categoría
            $builder
                ->add('title', TextType::class, ['label' => 'Titulo'])
                ->add('roleFilter', EntityType::class, [
                    'class' => Role::class,
                    'choice_label' => 'displayName',
                    'choice_value' => function(?Role $role) {
                        return $role ? $role->getId() : '';
                    },
                    'label' => 'Filtrar por Rol',
                    'required' => false,
                    'mapped' => false,
                    'placeholder' => 'Todos los roles',
                    'query_builder' => function (\Doctrine\ORM\EntityRepository $er) {
                        return $er->createQueryBuilder('r')
                            ->where('r.isActive = :active')
                            ->setParameter('active', true)
                            ->orderBy('r.category', 'ASC')
                            ->addOrderBy('r.displayName', 'ASC');
                    },
                    'group_by' => function(Role $role) {
                        $category = $role->getCategory();
                        $categories = Role::getCategories();
                        return $category && isset($categories[$category]) 
                            ? $categories[$category] 
                            : 'Sin Categoría';
                    },
                    'attr' => [
                        'class' => 'form-control',
                        'id' => 'role-filter-select'
                    ],
                ])
                ->add('doctor', EntityType::class, [
                    'class' => User::class,
                    'choice_label' => 'NombreApellido',
                    'choice_value' => function(?User $user) {
                        return $user ? $user->getId() : '';
                    },
                    'label' => 'Profesional',
                    'attr' => ['class' => 'predictivo', 'id' => 'booking-doctor-select'],
                    'query_builder' => function (EntityRepository $er) {
                        $qb = $er->createQueryBuilder('u')->where("JSON_CONTAINS (u.modalidad, '\"$this->pctr\"', '$') = 1");
                        if ( $this->pctr != '' ) {
                            return $qb;
                        } else {
                            return $er->createQueryBuilder('u')->where("1 = 1");
                        }
                    },
                ])
                ->add('modalidadFilter', ChoiceType::class, [
                    'label' => 'Filtrar por Modalidad',
                    'choices' => [
                        'Todos' => '',
                        'Ambulatorio' => '1',
                        'Internación' => '2',
                        'Hospital de día' => '3',
                        'ART' => '4',
                    ],
                    'required' => false,
                    'mapped' => false,
                    'attr' => [
                        'class' => 'form-control',
                        'id' => 'modalidad-filter-select'
                    ],
                    'placeholder' => 'Todas las modalidades',
                ])
                ->add('cliente', EntityType::class, [
                    'class' => Cliente::class,
                    'choice_label' => 'NombreApellido',
                    'label' => 'Paciente',
                    'attr' => ['class' => 'predictivo', 'id' => 'booking-cliente-select'],
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
