<?php

namespace App\Controller;

use App\Entity\Booking;
use App\Form\BookingType;
use App\Repository\BookingRepository;
use App\Repository\ClienteRepository;
use App\Repository\DoctorRepository;
use DateInterval;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Security;

/**
 * @Route("/booking")
 */
class BookingController extends AbstractController
{
    /**
     * @var Security
     */
    private $security;
    public function __construct(Security $security)
    {
        $this->security = $security;
    }

    /**
     * @Route("/", name="booking_index", methods={"GET"})
     */
    public function index(BookingRepository $bookingRepository): Response
    {
        return $this->render('booking/index.html.twig', [
            'bookings' => $bookingRepository->findAll(),
        ]);
    }

    /**
     * @Route("/getEvents", name="get_events", methods={"GET"})
     * @param BookingRepository $bookingRepository
     * @return Response
     */
    public function getEvents(BookingRepository $bookingRepository): Response
    {
        $eventos = $bookingRepository->findAll();
        $arrEventos = [];

        foreach ($eventos as $evento) {
            $arrEventos[] = [
                'id' => $evento->getId(),
                'start' => $evento->getBeginAtForEvent(),
                'end' => $evento->getEndAtForEvent(),
                'title' => $evento->getTitle()
            ];
        }

        return new JsonResponse($arrEventos);
        //return $arrEventos;
        /*return $this->render('booking/events.html.twig', [
            'events' => json_encode($arrEventos),
        ]);*/
    }

    /**
     * @Route("/calendar", name="booking_calendar", methods={"GET"})
     */
    public function calendar(DoctorRepository $doctorRepository, ClienteRepository $clienteRepository): Response
    {
        $booking = new Booking();

        $doctores = $doctorRepository->findAll();
        $clientes = $clienteRepository->findAll();
        $user = $this->security->getUser();
        $booking->setUser($user);

        //$form = $this->createForm(BookingType::class, $booking);

        return $this->render('booking/calendar.html.twig', [
            'clientes' => $clientes,
            'doctores' => $doctores,
        ]);
    }

    /**
     * @Route("/calendar/filter", name="booking_calendar_filter", methods={"GET"})
     */
    public function calendarFilter(Request $request, DoctorRepository $doctorRepository, ClienteRepository $clienteRepository): Response
    {
        $doctores = $request->query->get('doctores');
        $clientes = $request->query->get('clientes');


        $doctores = $doctorRepository->findBy(array('id' => array_values($doctores)));
        dd($doctores);
        $clientes = $clienteRepository->findAll();
        $user = $this->security->getUser();


        //$form = $this->createForm(BookingType::class, $booking);

        return $this->render('booking/calendar.html.twig', [
            'clientes' => $clientes,
            'doctores' => $doctores,
        ]);
    }

    /**
     * @Route("/new", name="booking_new", methods={"GET","POST"})
     */
    public function new(Request $request): Response
    {
        $booking = new Booking();

        $user = $this->security->getUser();
        $booking->setUser($user);

        $beginAt = !empty($request->get('date')) ? new \DateTime($request->get('date')) : new \DateTime();

        $minutes_to_add = 30;

        $endAt = !empty($request->get('date')) ? new \DateTime($request->get('date')) : new \DateTime();
        $endAt->add(new DateInterval('PT' . $minutes_to_add . 'M'));

        $booking->setBeginAt($beginAt);
        $booking->setEndAt($endAt);

        $form = $this->createForm(BookingType::class, $booking);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->persist($booking);
            $entityManager->flush();

            return $this->redirectToRoute('booking_calendar');
        }

        return $this->render('booking/new.html.twig', [
            'booking' => $booking,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/{id}", name="booking_show", methods={"GET"})
     */
    public function show(Booking $booking): Response
    {
        return $this->render('booking/show.html.twig', [
            'booking' => $booking,
        ]);
    }

    /**
     * @Route("/{id}/edit", name="booking_edit", methods={"GET","POST"})
     */
    public function edit(Request $request, Booking $booking): Response
    {
        $form = $this->createForm(BookingType::class, $booking);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->getDoctrine()->getManager()->flush();

            return $this->redirectToRoute('booking_calendar');
        }

        return $this->render('booking/edit.html.twig', [
            'booking' => $booking,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/{id}", name="booking_delete", methods={"DELETE"})
     */
    public function delete(Request $request, Booking $booking): Response
    {
        if ($this->isCsrfTokenValid('delete'.$booking->getId(), $request->request->get('_token'))) {
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->remove($booking);
            $entityManager->flush();
        }

        return $this->redirectToRoute('booking_calendar');
    }



}
