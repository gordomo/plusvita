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

class CalendarSubscriber implements EventSubscriberInterface
{
    private $bookingRepository;
    private $doctorRepository;
    private $clienteRepository;
    private $userRepository;
    private $router;

    public function __construct( BookingRepository $bookingRepository, DoctorRepository $doctorRepository, ClienteRepository $clienteRepository, UserRepository $userRepository, UrlGeneratorInterface $router) {
        $this->bookingRepository = $bookingRepository;
        $this->doctorRepository = $doctorRepository;
        $this->clienteRepository = $clienteRepository;
        $this->userRepository = $userRepository;
        $this->router = $router;
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

        // Modify the query to fit to your entity and needs
        // Change booking.beginAt by your start date property
        $bookings = $this->bookingRepository
            ->createQueryBuilder('booking')
            ->where('booking.beginAt BETWEEN :start and :end OR booking.endAt BETWEEN :start and :end')
            ->setParameter('start', $start->format('Y-m-d H:i:s'))
            ->setParameter('end', $end->format('Y-m-d H:i:s'));

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
            $docIds = json_decode($filters['doctor_id']);
            // Obtener doctores por IDs
            $doctors = $this->doctorRepository->findBy(['id' => array_values($docIds)]);
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
                // Si no hay doctores con esos IDs, no mostrar ningún turno
                $bookings->andWhere('1 = 0');
            }
        }
        if (!empty($filters['cliente_id'])) {
            $cliIds = json_decode($filters['cliente_id']);
            $cliente = $this->clienteRepository->findBy(array('id' => array_values($cliIds)));
            $bookings->andWhere('booking.cliente IN (:cliente)')
                ->setParameter('cliente', $cliente);
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