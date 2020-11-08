<?php

namespace App\EventSubscriber;

use App\Repository\BookingRepository;
use App\Repository\ClienteRepository;
use App\Repository\DoctorRepository;
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
    private $router;

    public function __construct( BookingRepository $bookingRepository, DoctorRepository $doctorRepository, ClienteRepository $clienteRepository, UrlGeneratorInterface $router) {
        $this->bookingRepository = $bookingRepository;
        $this->doctorRepository = $doctorRepository;
        $this->clienteRepository = $clienteRepository;
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
            $doctor = $this->doctorRepository->findByContrato($ctr);
            $bookings->andWhere('booking.doctor IN (:doctor)')
                ->setParameter('doctor', $doctor);
        }

        if (!empty($filters['doctor_id'])) {
            $docIds = json_decode($filters['doctor_id']);
            $doctor = $this->doctorRepository->findBy(array('id' => array_values($docIds)));
            $bookings->andWhere('booking.doctor IN (:doctor)')
                     ->setParameter('doctor', $doctor);
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

            $doctor = $booking->getDoctor();
            $color = '#2196f3';

            switch ($doctor->getModalidad()[0]) {
                case 'Mucamo/a':
                case 'Enfermero/a':
                case 'Auxiliar de enfermeria':
                case 'Asistente de enfermeria':
                case 'Mantenimiento':
                case 'Cocinero':
                case 'Ayudante de cocina':
                case 'Administrativo':
                case 'Recepcionista':
                case 'Coordinador de pisos':
                case 'Coordinador general':
                case 'Coordinador de enfermeria':
                    $color = '#b088be';
                    break;

                case 'Nutricionista':
                case 'Director medico':
                case 'Sub director medico':
                case 'Trabajadora social':
                case 'Psiquiatra':
                case 'Infectologo':
                case 'Contador':
                case 'Abogado':
                case 'Estudio contable':
                case 'Directivo':
                case 'Programador':
                    $color = '#60b9b9';
                    break;

                case 'Profesional por prestacion':
                case 'Medico de guardia':
                case 'Kinesiologo':
                case 'Kinesiologo respiratorio':
                case 'Terapista ocupacional':
                case 'Fonoaudiologo':
                case 'Psicologo':
                case 'Fisiatra':
                case 'Neurologo':
                case 'Cardiologo':
                case 'Urologo':
                case 'Hematologo':
                case 'Neumonologo':
                    $color = '#889cbe';
                    break;
                case 'Cirujano':
                case 'Traumatologo':
                    $color = '#e64891';
                    break;
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

            if($booking->getDias()) {
                $bookingEvent->addOption(
                    'daysOfWeek',
                    $booking->getDias()
                );
                $bookingEvent->addOption(
                    'startRecur',
                    $booking->getDesdeEvent()
                );
                $bookingEvent->addOption(
                    'endRecur',
                    $booking->getHastaEvent()
                );
            }

            // finally, add the event to the CalendarEvent to fill the calendar
            $calendar->addEvent($bookingEvent);
        }
    }
}