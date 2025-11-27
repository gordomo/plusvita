<?php

namespace App\EventSubscriber;

use App\Repository\BookingRepository;
use App\Repository\ClienteRepository;
use App\Repository\DoctorRepository;
use App\Repository\UserRepository;
use CalendarBundle\CalendarEvents;
use CalendarBundle\Entity\Event;
use CalendarBundle\Event\CalendarEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Security;

class CalendarSubscriber implements EventSubscriberInterface
{
    private $bookingRepository;
    private $doctorRepository;
    private $clienteRepository;
    private $userRepository;
    private $router;
    private $security;

    public function __construct( BookingRepository $bookingRepository, DoctorRepository $doctorRepository, ClienteRepository $clienteRepository, UserRepository $userRepository, UrlGeneratorInterface $router, Security $security) {
        $this->bookingRepository = $bookingRepository;
        $this->doctorRepository = $doctorRepository;
        $this->clienteRepository = $clienteRepository;
        $this->userRepository = $userRepository;
        $this->router = $router;
        $this->security = $security;
    }

    public static function getSubscribedEvents()
    {
        return [
            CalendarEvents::SET_DATA => 'onCalendarSetData',
        ];
    }

    public function onCalendarSetData(CalendarEvent $calendar)
    {
        $start = $calendar->getStart();
        $end = $calendar->getEnd();
        $filters = $calendar->getFilters();
        
        // Si filters es un string JSON, decodificarlo
        if (is_string($filters)) {
            $decodedFilters = json_decode($filters, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decodedFilters)) {
                $filters = $decodedFilters;
            } else {
                // Si no se puede decodificar, inicializar como array vacío
                $filters = [];
            }
        }
        
        // Asegurar que filters sea un array
        if (!is_array($filters)) {
            $filters = [];
        }

        // Modify the query to fit to your entity and needs
        // Change booking.beginAt by your start date property
        $bookings = $this->bookingRepository
            ->createQueryBuilder('booking')
            ->where('booking.beginAt BETWEEN :start and :end OR booking.endAt BETWEEN :start and :end')
            ->setParameter('start', $start->format('Y-m-d H:i:s'))
            ->setParameter('end', $end->format('Y-m-d H:i:s'));

        // Filtrar por permisos: si el usuario no tiene permisos de administración,
        // solo mostrar sus propios turnos (donde él es el doctor)
        $currentUser = $this->security->getUser();
        $canManageAgenda = false;
        
        if ($currentUser) {
            $canManageAgenda = $this->security->isGranted('agenda.manage') || $this->security->isGranted('ROLE_ADMIN');
            
            if (!$canManageAgenda) {
                // Si no tiene permisos de administración, solo mostrar turnos donde él es el doctor
                $bookings->andWhere('booking.doctor = :currentUser')
                         ->setParameter('currentUser', $currentUser);
            }
        } else {
            // Si no hay usuario autenticado, no mostrar ningún turno
            $bookings->andWhere('1 = 0');
        }

        if (!empty($filters['ctr'])) {
            $ctr = $filters['ctr'];
            // Obtener doctores por contrato (modalidad)
            $doctors = $this->doctorRepository->findByContrato($ctr);
            // Convertir doctores a usuarios por email
            $userEmails = [];
            foreach ($doctors as $doctor) {
                $userEmails[] = $doctor->getEmail();
            }
            if (!empty($userEmails)) {
                $users = $this->userRepository->findBy(['email' => $userEmails]);
                $bookings->andWhere('booking.doctor IN (:doctor)')
                    ->setParameter('doctor', $users);
            } else {
                // Si no hay doctores con ese contrato, no mostrar ningún turno
                $bookings->andWhere('1 = 0');
            }
        }

        if (!empty($filters['doctor_id'])) {
            try {
                $docIds = $filters['doctor_id'];
                
                // Si es un string, intentar decodificarlo como JSON
                if (is_string($docIds)) {
                    $decoded = json_decode($docIds, true);
                    if (json_last_error() === JSON_ERROR_NONE && $decoded !== null) {
                        $docIds = $decoded;
                    }
                }
                
                // Asegurar que sea un array
                if (!is_array($docIds)) {
                    $docIds = [$docIds];
                }
                
                // Filtrar valores válidos (solo números enteros)
                $docIds = array_filter(array_map('intval', $docIds), function($id) {
                    return $id > 0;
                });
                
                if (!empty($docIds)) {
                    // Obtener doctores por IDs
                    $doctors = $this->doctorRepository->findBy(['id' => array_values($docIds)]);
                    // Convertir doctores a usuarios por email
                    $userEmails = [];
                    foreach ($doctors as $doctor) {
                        $userEmails[] = $doctor->getEmail();
                    }
                    if (!empty($userEmails)) {
                        $users = $this->userRepository->findBy(['email' => $userEmails]);
                        if (!empty($users)) {
                            $bookings->andWhere('booking.doctor IN (:doctor)')
                                     ->setParameter('doctor', $users);
                        } else {
                            // Si no hay usuarios correspondientes, no mostrar ningún turno
                            $bookings->andWhere('1 = 0');
                        }
                    } else {
                        // Si no hay doctores con esos IDs, no mostrar ningún turno
                        $bookings->andWhere('1 = 0');
                    }
                } else {
                    // Si no hay IDs válidos, no mostrar ningún turno
                    $bookings->andWhere('1 = 0');
                }
            } catch (\Exception $e) {
                // En caso de error, no aplicar filtro de doctor
                // Log del error si es necesario
            }
        }
        if (!empty($filters['cliente_id'])) {
            try {
                $cliIds = $filters['cliente_id'];
                
                // Si es un string, intentar decodificarlo como JSON
                if (is_string($cliIds)) {
                    $decoded = json_decode($cliIds, true);
                    if (json_last_error() === JSON_ERROR_NONE && $decoded !== null) {
                        $cliIds = $decoded;
                    }
                }
                
                // Asegurar que sea un array
                if (!is_array($cliIds)) {
                    $cliIds = [$cliIds];
                }
                
                // Filtrar valores válidos (solo números enteros)
                $cliIds = array_filter(array_map('intval', $cliIds), function($id) {
                    return $id > 0;
                });
                
                if (!empty($cliIds)) {
                    $cliente = $this->clienteRepository->findBy(array('id' => array_values($cliIds)));
                    if (!empty($cliente)) {
                        $bookings->andWhere('booking.cliente IN (:cliente)')
                            ->setParameter('cliente', $cliente);
                    } else {
                        // Si no hay clientes con esos IDs, no mostrar ningún turno
                        $bookings->andWhere('1 = 0');
                    }
                } else {
                    // Si no hay IDs válidos, no aplicar filtro
                }
            } catch (\Exception $e) {
                // En caso de error, no aplicar filtro de cliente
                // Log del error si es necesario
            }
        }

        $bookings = $bookings->getQuery()->getResult();


        foreach ($bookings as $booking) {
            // this create the events with your data (here booking data) to fill calendar
            $bookingEvent = new Event(
                $booking->getTitle(),
                $booking->getBeginAt(),
                $booking->getEndAt() // If the end date is null or not defined, a all day event is created.
            );

            /*
             * Add custom options to events
             *
             * For more information see: https://fullcalendar.io/docs/event-object
             * and: https://github.com/fullcalendar/fullcalendar/blob/master/src/core/options.ts
             */

            // booking->getDoctor() ahora devuelve un User, necesitamos buscar el Doctor asociado por email
            $doctorUser = $booking->getDoctor();
            $color = '#2196f3'; // Color por defecto
            
            if ($doctorUser) {
                try {
                    $doctor = $this->doctorRepository->createQueryBuilder('d')
                        ->where('d.email = :email')
                        ->setParameter('email', $doctorUser->getEmail())
                        ->getQuery()
                        ->getOneOrNullResult();
                    
                    if ($doctor && $doctor->getColor()) {
                        $color = $doctor->getColor();
                    }
                } catch (\Exception $e) {
                    // Si hay error al buscar el doctor, usar color por defecto
                }
            }

            $bookingEvent->setOptions([
                'backgroundColor' => $color,
                'borderColor' => $color,
            ]);
            $bookingEvent->addOption(
                'url',
                $this->router->generate('booking_show', [
                    'id' => $booking->getId(),
                ])
            );

            // finally, add the event to the CalendarEvent to fill the calendar
            $calendar->addEvent($bookingEvent);
        }
    }
}