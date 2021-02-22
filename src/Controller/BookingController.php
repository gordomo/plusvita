<?php

namespace App\Controller;

use App\Entity\Booking;
use App\Form\BookingType;
use App\Repository\BookingRepository;
use App\Repository\ClienteRepository;
use App\Repository\DoctorRepository;
use App\Repository\ObraSocialRepository;
use DateInterval;
use PhpParser\Comment\Doc;
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
    public function index(Request $request, BookingRepository $bookingRepository, DoctorRepository $doctorRepository, ClienteRepository $clienteRepository, ObraSocialRepository $obraSocialRepository): Response
    {
        $user = $this->getUser();
        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        $obrasSociales = $obraSocialRepository->findAll();
        $obArray = [];
        foreach ( $obrasSociales as $ob ) {
            $obArray[$ob->getId()] = $ob->getNombre();
        }

        $contratoSelected = $request->query->get('contrato') ?? '';

        $desde = $request->query->get('from') ?? '';
        $hasta = $request->query->get('to') ?? '';
        $clienteSelected = $request->query->get('cliente') ?? '';
        $doctorSelected = $request->query->get('doctor') ?? '';
        $completadosSi = (!empty($request->query->get('completadosSi')) && $request->query->get('completadosSi') == 'on') ? true : '';
        $completadosNo = (!empty($request->query->get('completadosNo')) && $request->query->get('completadosNo') == 'on') ? true : '';

        $contrato = $request->query->get('ctr');
        $ctrsArray = [0 => $contrato];

        if(!empty($contrato)) {
            $doctores = $doctorRepository->findByContrato($contrato);
        } else {
            $doctores = $doctorRepository->findAll();
        }

        $clientes = $clienteRepository->findAllActivos(new \DateTime());
        $directo = [
            'Nutricionista',
            'Director medico',
            'Sub director medico',
            'Trabajadora social',
            'Psiquiatra',
            'Infectologo',
            'Contador',
            'Abogado',
            'Estudio contable',
            'Directivo',
            'Programador',
        ];
        $prestacion = [
            'Profesional por prestacion',
            'Medico de guardia',
            'Kinesiologo',
            'Kinesiologo respiratorio',
            'Terapista ocupacional',
            'Fonoaudiologo',
            'Psicologo',
            'Fisiatra',
            'Neurologo',
            'Cardiologo',
            'Urologo',
            'Hematologo',
            'Neumonologo',
        ];
        $sinContrato = [
            'Cirujano',
            'Traumatologo',
            'Neumonologo',
        ];
        $contratos = ['directo' => $directo, 'prestacion' => $prestacion, 'sinContrato' => $sinContrato];


        $completados = $completadosSi;

        if($completadosNo) {
            $completados = false;
        }

        if(($completadosSi && $completadosNo) || ($completadosSi == '' && $completadosNo == '')) {
            $completados = '';
        }

        $booking = $bookingRepository->turnosConFiltro($doctorSelected, $clienteSelected, $desde, $hasta, $completados);

        return $this->render('booking/index.html.twig', [
            'bookings' => $booking,
            'desde' => $desde,
            'hasta' => $hasta,
            'clientes' => $clientes,
            'doctores' => $doctores,
            'contratos' => $contratos,
            'ctrsArray' => $ctrsArray,
            'obrasSociales' => $obArray,
            'completadosSi' => $completadosSi,
            'completadosNo' => $completadosNo,
            'clienteSelected' => $clienteSelected,
            'doctorSelected' => $doctorSelected
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
    public function calendar(Request $request, DoctorRepository $doctorRepository, ClienteRepository $clienteRepository): Response
    {
        $user = $this->security->getUser();
        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        $booking = new Booking();

        $contrato = $request->query->get('ctr');
        $ctrsArray = [0 => $contrato];

        if(!empty($contrato)) {
            $doctores = $doctorRepository->findByContrato($contrato);
        } else {
            $doctores = $doctorRepository->findAll();
        }

        $clientes = $clienteRepository->findAllActivos(new \DateTime());
        $directo = [
            'Nutricionista',
            'Director medico',
            'Sub director medico',
            'Trabajadora social',
            'Psiquiatra',
            'Infectologo',
            'Contador',
            'Abogado',
            'Estudio contable',
            'Directivo',
            'Programador',
        ];
        $prestacion = [
            'Profesional por prestacion',
            'Medico de guardia',
            'Kinesiologo',
            'Kinesiologo respiratorio',
            'Terapista ocupacional',
            'Fonoaudiologo',
            'Psicologo',
            'Fisiatra',
            'Neurologo',
            'Cardiologo',
            'Urologo',
            'Hematologo',
            'Neumonologo',
        ];
        $sinContrato = [
            'Cirujano',
            'Traumatologo',
            'Neumonologo',
        ];
        $contratos = ['directo' => $directo, 'prestacion' => $prestacion, 'sinContrato' => $sinContrato];

        $booking->setUser($user);

        //$form = $this->createForm(BookingType::class, $booking);
        //TODO calcular businessHours de acuerdo a los doctores disponibles
        $businessHours = $this->getBusinessHours($doctores);

        return $this->render('booking/calendar.html.twig', [
            'clientes' => $clientes,
            'doctores' => $doctores,
            'contratos' => $contratos,
            'ctrsArray' => $ctrsArray,
            'businessHours' => $businessHours
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
    public function new(Request $request, DoctorRepository $doctorRepository, BookingRepository $bookingRepository): Response
    {
        $booking = new Booking();
        $error = false;
        $yaTieneTurno = false;

        $user = $this->security->getUser();
        $booking->setUser($user);

        $beginAt = !empty($request->get('date')) ? new \DateTime($request->get('date')) : new \DateTime();

        $minutes_to_add = 30;

        $endAt = !empty($request->get('date')) ? new \DateTime($request->get('date')) : new \DateTime();
        $endAt->add(new DateInterval('PT' . $minutes_to_add . 'M'));

        $booking->setBeginAt($beginAt);
        $booking->setEndAt($endAt);

        $ctr = !empty($request->get('ctr')) ? $request->get('ctr') : '';

        $form = $this->createForm(BookingType::class, $booking, ['ctr' => $ctr, 'isNew' => true]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            $doctor = $booking->getDoctor();
            $cliente = $booking->getCliente();
            $newBeginAt = !empty($booking->getBeginAt()) ? $booking->getBeginAt() : new \DateTime();
            $newEndAt = !empty($booking->getEndAt()) ? $booking->getEndAt() : new \DateTime();

            $horaTurno = $newBeginAt->format('H');
            $minutosTurno = $newBeginAt->format('i');
            $segundosTurno = $newBeginAt->format('s');

            $dias = !empty($booking->getDias()) ? $booking->getDias() : [1,2,3,4,5,6,7];
            $desde = !empty($booking->getDesde()) ? $booking->getDesde() : $newBeginAt;
            $hasta = !empty($booking->getHasta()) ? $booking->getHasta()->modify('+1 day') : $newEndAt;

            $arrayDeEventos = [];
            $arrayDeErrores = [];

            if ($desde->format('Y-m-d') == $hasta->format('Y-m-d')) {
                //Primero me fijo si ya existe un turno para este cliente con este doctor
                $bookings = $bookingRepository->findBy(['doctor' => $doctor, 'beginAt' => $desde, 'cliente' => $cliente]);
                if (count($bookings) > 0) {
                    $error = true;
                    $yaTieneTurno = true;
                    $arrayDeErrores[] = $desde->format(DATE_ATOM);
                } else {
                    $bookings = $bookingRepository->findBy(['doctor' => $doctor, 'beginAt' => $desde]);
                    if ( count($bookings) >= $doctor->getMaxCliTurno() && $doctor->getMaxCliTurno() != null || ($doctor->getMaxCliTurno() == null ) ) {
                        $error = true;
                        $arrayDeErrores[] = $desde->format(DATE_ATOM);
                    } else {
                        $arrayDeEventos[] = $booking;
                    }
                }

            } else {
                for($date = $desde; $date <= $hasta; $date->modify('+1 day')) {

                    $date->setTime($horaTurno, $minutosTurno, $segundosTurno);
                    $end = $newEndAt->format(DATE_ATOM);
                    $start = $date->format(DATE_ATOM);

                    if(in_array($date->format('N'), $dias)) {
                        $bookings = $bookingRepository->findBy(['doctor' => $doctor, 'beginAt' => $desde, 'cliente' => $cliente]);
                        if(count($bookings) > 0) {
                            $error = true;
                            $yaTieneTurno = true;
                            $arrayDeErrores[] = $desde->format(DATE_ATOM);
                        } else {
                            $bookings = $bookingRepository->findBy(['doctor' => $doctor, 'beginAt' => $date]);
                            if ( count($bookings) >= $doctor->getMaxCliTurno() && $doctor->getMaxCliTurno() != null || ($doctor->getMaxCliTurno() == null ) ) {
                                $error = true;
                                $arrayDeErrores[] = $start;
                            } else {
                                $book = new Booking();
                                $book->setBeginAt(new \DateTime($start));
                                $book->setEndAt(new \DateTime($end));
                                $newEndAt->modify('+1 day');

                                $book->setDoctor($doctor);
                                $book->setCliente($booking->getCliente());

                                $book->setTitle($booking->getTitle());
                                $book->setUser($booking->getUser());
                                $arrayDeEventos[] = $book;
                            }
                        }
                    }
                }
            }

            $guardarIgual = false;
            if(!$error || (!empty($request->get('guardarIgual')))) {
                if(count($arrayDeEventos) == 0) {
                    $arrayDeEventos[] = $booking;
                }
                foreach($arrayDeEventos as $book) {
                    $entityManager = $this->getDoctrine()->getManager();
                    $entityManager->persist($book);
                    $entityManager->flush();
                }
                return $this->redirectToRoute('booking_calendar');
            } else {
                    if($yaTieneTurno) {
                        $stringError = "Los siguientes turnos no pueden ser agendados, porque el paciente ya tiene un turno asignado en ese día y horario con ese profesional <br>" ;
                    } else {
                        $stringError = "Los siguientes turnos no pueden ser agendados, porque superan el total de turnos para el profesional en el mismo horario: <br>" ;
                    }

                    foreach ($arrayDeErrores as $diaConError) {
                        $diaConError = new \DateTime($diaConError);
                        $stringError .= $diaConError->format('Y-m-d H:i:s') . '<br>';
                    }
                    if(count($arrayDeEventos) > 0) {
                        $guardarIgual = true;
                        $stringError .= '<br>Los siguientes turnos si pueden ser guardados: <br>';
                        foreach ($arrayDeEventos as $eventosOk) {
                            $stringError .= $eventosOk->getBeginAt()->format('Y-m-d H:i:s') . '<br>';
                        }
                        $stringError .= 'Para agendar los turnos disponibles presione el boton GUARDAR. <br>O precione CANCELAR para seleccionar diferentes horarios';
                    }
                return $this->render('booking/new.html.twig', [
                    'booking' => $booking,
                    'form' => $form->createView(),
                    'error' => $stringError,
                    'guardarIgual' => $guardarIgual,
                ]);
            }
        }

        return $this->render('booking/new.html.twig', [
            'booking' => $booking,
            'form' => $form->createView(),
            'error' => $error ?? 0,
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
    public function edit(Request $request, Booking $booking, DoctorRepository $doctorRepository, BookingRepository $bookingRepository): Response
    {
        $form = $this->createForm(BookingType::class, $booking);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $doctor = $booking->getDoctor();
            $newBeginAt = $booking->getBeginAt();
            $bookings = $bookingRepository->findBy(['doctor' => $doctor, 'beginAt' => $newBeginAt]);

            if ( count($bookings) >= $doctor->getMaxCliTurno() && $doctor->getMaxCliTurno() != null || ($doctor->getMaxCliTurno() == null ) ) {
                $error = 'El turno no puede ser movido a esa fecha/horario porque supera el número máximo de pacientes por turno que puede atender el profesional';
            } else {
                $this->getDoctrine()->getManager()->flush();
                return $this->redirectToRoute('booking_calendar');
            }
        }

        return $this->render('booking/edit.html.twig', [
            'booking' => $booking,
            'form' => $form->createView(),
            'error' => $error ?? 0
        ]);
    }

    /**
     * @Route("/{id}/{start}/{end}", name="booking_edit_ajax", methods={"GET","POST"})
     */
    public function ajaxEdit($id, $start, $end, BookingRepository $bookingRepository, DoctorRepository $doctorRepository): Response
    {
        $error = false;
        $message = 'ok';
        try {
            $beginAt = new \DateTime(substr($start, 0, 33));
            $beginAt->modify('+3 hours');
            $endAt = new \DateTime(substr($end, 0, 33));
            $endAt->modify('+3 hours');
            $booking = $bookingRepository->find($id);
            $doctor = $doctorRepository->find($booking->getDoctor()->getId());
            $bookings = $bookingRepository->findBy(['doctor' => $doctor, 'beginAt' => $beginAt]);

            if ( count($bookings) >= $doctor->getMaxCliTurno() && $doctor->getMaxCliTurno() != null || ($doctor->getMaxCliTurno() == null ) ) {
                $error = true;
                $message = 'El turno no puede ser movido a esa fecha/horario porque supera el número máximo de pacientes por turno que puede atender el profesional';
            } else {
                $booking->setBeginAt($beginAt);
                $booking->setEndAt($endAt);
                $entityManager = $this->getDoctrine()->getManager();
                $entityManager->persist($booking);
                $entityManager->flush();
            }

            return new JsonResponse(['error' => $error, 'message' => $message]);

        } catch (\Exception $e) {
            return new JsonResponse(['error' => true, 'code' => $e->getCode(), 'message' => $e->getMessage()]);
        }
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

    /**
     * @Route("/delete/multiple", name="booking_multi_delete", methods={"GET"})
     */
    public function multiDelete(Request $request, BookingRepository $bookingRepository): Response
    {
        $user = $this->getUser();
        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        $ids = $request->query->get('ids') ?? '';
        $ids = explode(',', $ids);

        $entityManager = $this->getDoctrine()->getManager();

        foreach ($ids as $id) {
            $booking = $bookingRepository->find($id);

            if(!$booking->getCompletado()) {
                $entityManager->remove($booking);
            }
        }

        $entityManager->flush();

        return $this->redirectToRoute('booking_index');
    }

    private function getBusinessHours(array $doctores)
    {
        foreach ($doctores as $doctor) {
            $doctorsBusinessHours = $doctor->getBusinessHours();

            if (!empty($doctorsBusinessHours)) {
                if (!isset($businessHours)) {
                    if (isset($doctorsBusinessHours[1])) {
                        $businessHours['lunes'] = $doctorsBusinessHours[1];
                    }
                    if (isset($doctorsBusinessHours[2])) {
                        $businessHours['martes'] = $doctorsBusinessHours[2];
                    }
                    if (isset($doctorsBusinessHours[3])) {
                        $businessHours['miercoles'] = $doctorsBusinessHours[3];
                    }
                    if (isset($doctorsBusinessHours[4])) {
                        $businessHours['jueves'] = $doctorsBusinessHours[4];
                    }
                    if (isset($doctorsBusinessHours[5])) {
                        $businessHours['viernes'] = $doctorsBusinessHours[5];
                    }
                    if (isset($doctorsBusinessHours[6])) {
                        $businessHours['sabado'] = $doctorsBusinessHours[6];
                    }
                } else {
                    //lunes
                    if (isset($doctorsBusinessHours[1]) && (isset($businessHours['lunes'])) && $businessHours['lunes']['desde'] > $doctorsBusinessHours[1]['desde']) {
                        $businessHours['lunes']['desde'] = $doctorsBusinessHours[1]['desde'];
                    }
                    if (isset($doctorsBusinessHours[1]) && (isset($businessHours['lunes'])) && $businessHours['lunes']['hasta'] < $doctorsBusinessHours[1]['hasta']) {
                        $businessHours['lunes']['hasta'] = $doctorsBusinessHours[1]['hasta'];
                    }
                    if (isset($doctorsBusinessHours[1]) && (isset($businessHours['lunes'])) && $businessHours['lunes']['ydesde'] > $doctorsBusinessHours[1]['ydesde']) {
                        $businessHours['lunes']['ydesde'] = $doctorsBusinessHours[1]['ydesde'];
                    }
                    if (isset($doctorsBusinessHours[1]) && (isset($businessHours['lunes'])) && $businessHours['lunes']['yhasta'] < $doctorsBusinessHours[1]['yhasta']) {
                        $businessHours['lunes']['yhasta'] = $doctorsBusinessHours[1]['yhasta'];
                    }
                    //martes
                    if (isset($doctorsBusinessHours[2]) && (isset($businessHours['martes'])) && $businessHours['martes']['desde'] > $doctorsBusinessHours[2]['desde']) {
                        $businessHours['martes']['desde'] = $doctorsBusinessHours[2]['desde'];
                    }
                    if (isset($doctorsBusinessHours[2]) && (isset($businessHours['martes'])) && $businessHours['martes']['hasta'] < $doctorsBusinessHours[2]['hasta']) {
                        $businessHours['martes']['hasta'] = $doctorsBusinessHours[2]['hasta'];
                    }
                    if (isset($doctorsBusinessHours[2]) && (isset($businessHours['martes'])) && $businessHours['martes']['ydesde'] > $doctorsBusinessHours[2]['ydesde']) {
                        $businessHours['martes']['ydesde'] = $doctorsBusinessHours[2]['ydesde'];
                    }
                    if (isset($doctorsBusinessHours[2]) && (isset($businessHours['martes'])) && $businessHours['martes']['yhasta'] < $doctorsBusinessHours[2]['yhasta']) {
                        $businessHours['martes']['yhasta'] = $doctorsBusinessHours[2]['yhasta'];
                    }
                    //miercoles
                    if (isset($doctorsBusinessHours[3]) && (isset($businessHours['miercoles'])) && $businessHours['miercoles']['desde'] > $doctorsBusinessHours[3]['desde']) {
                        $businessHours['miercoles']['desde'] = $doctorsBusinessHours[3]['desde'];
                    }
                    if (isset($doctorsBusinessHours[3]) && (isset($businessHours['miercoles'])) && $businessHours['miercoles']['hasta'] < $doctorsBusinessHours[3]['hasta']) {
                        $businessHours['miercoles']['hasta'] = $doctorsBusinessHours[3]['hasta'];
                    }
                    if (isset($doctorsBusinessHours[3]) && (isset($businessHours['miercoles'])) && $businessHours['miercoles']['ydesde'] > $doctorsBusinessHours[3]['ydesde']) {
                        $businessHours['miercoles']['ydesde'] = $doctorsBusinessHours[3]['ydesde'];
                    }
                    if (isset($doctorsBusinessHours[3]) && (isset($businessHours['miercoles'])) && $businessHours['miercoles']['yhasta'] < $doctorsBusinessHours[3]['yhasta']) {
                        $businessHours['miercoles']['yhasta'] = $doctorsBusinessHours[3]['yhasta'];
                    }
                    //jueves
                    if (isset($doctorsBusinessHours[4]) && (isset($businessHours['jueves'])) && $businessHours['jueves']['desde'] > $doctorsBusinessHours[4]['desde']) {
                        $businessHours['jueves']['desde'] = $doctorsBusinessHours[4]['desde'];
                    }
                    if (isset($doctorsBusinessHours[4]) && (isset($businessHours['jueves'])) && $businessHours['jueves']['hasta'] < $doctorsBusinessHours[4]['hasta']) {
                        $businessHours['jueves']['hasta'] = $doctorsBusinessHours[4]['hasta'];
                    }
                    if (isset($doctorsBusinessHours[4]) && (isset($businessHours['jueves'])) && $businessHours['jueves']['ydesde'] > $doctorsBusinessHours[4]['ydesde']) {
                        $businessHours['jueves']['ydesde'] = $doctorsBusinessHours[4]['ydesde'];
                    }
                    if (isset($doctorsBusinessHours[4]) && (isset($businessHours['jueves'])) && $businessHours['jueves']['yhasta'] < $doctorsBusinessHours[4]['yhasta']) {
                        $businessHours['jueves']['yhasta'] = $doctorsBusinessHours[4]['yhasta'];
                    }
                    //viernes
                    if (isset($doctorsBusinessHours[5]) && (isset($businessHours['viernes'])) && $businessHours['viernes']['desde'] > $doctorsBusinessHours[5]['desde']) {
                        $businessHours['viernes']['desde'] = $doctorsBusinessHours[5]['desde'];
                    }
                    if (isset($doctorsBusinessHours[5]) && (isset($businessHours['viernes'])) && $businessHours['viernes']['hasta'] < $doctorsBusinessHours[5]['hasta']) {
                        $businessHours['viernes']['hasta'] = $doctorsBusinessHours[5]['hasta'];
                    }
                    if (isset($doctorsBusinessHours[5]) && (isset($businessHours['viernes'])) && $businessHours['viernes']['ydesde'] > $doctorsBusinessHours[5]['ydesde']) {
                        $businessHours['viernes']['ydesde'] = $doctorsBusinessHours[5]['ydesde'];
                    }
                    if (isset($doctorsBusinessHours[5]) && (isset($businessHours['viernes'])) && $businessHours['viernes']['yhasta'] < $doctorsBusinessHours[5]['yhasta']) {
                        $businessHours['viernes']['yhasta'] = $doctorsBusinessHours[5]['yhasta'];
                    }
                    //sabado
                    if (isset($doctorsBusinessHours[6]) && (isset($businessHours['sabado'])) && $businessHours['sabado']['desde'] > $doctorsBusinessHours[6]['desde']) {
                        $businessHours['sabado']['desde'] = $doctorsBusinessHours[6]['desde'];
                    }
                    if (isset($doctorsBusinessHours[6]) && (isset($businessHours['sabado'])) && $businessHours['sabado']['hasta'] < $doctorsBusinessHours[6]['hasta']) {
                        $businessHours['sabado']['hasta'] = $doctorsBusinessHours[6]['hasta'];
                    }
                    if (isset($doctorsBusinessHours[6]) && (isset($businessHours['sabado'])) && $businessHours['sabado']['ydesde'] > $doctorsBusinessHours[6]['ydesde']) {
                        $businessHours['sabado']['ydesde'] = $doctorsBusinessHours[6]['ydesde'];
                    }
                    if (isset($doctorsBusinessHours[6]) && (isset($businessHours['sabado'])) && $businessHours['sabado']['yhasta'] < $doctorsBusinessHours[6]['yhasta']) {
                        $businessHours['sabado']['yhasta'] = $doctorsBusinessHours[6]['yhasta'];
                    }
                }
            }

        }

        return $businessHours ?? [];
    }

    /**
     * @Route("/limpiar/turnos", name="booking_limpiar", methods={"GET"})
     */
    public function limpiarTurnos(Request $request, BookingRepository $bookingRepository, ClienteRepository $clienteRepository) {
        $user = $this->getUser();
        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        $clientes = $clienteRepository->findAllInactivos(new \DateTime());
        $entityManager = $this->getDoctrine()->getManager();

        foreach ($clientes as $cliente) {
            $fechaDeEgresoString = $cliente->getFEgreso()->setTime(23, 59, 59)->format('Y-m-d H:i:s');
            $turnosDePacienteInactivo = $bookingRepository->turnosConFiltro('', $cliente, $fechaDeEgresoString);

            foreach($turnosDePacienteInactivo as $turnoDePacienteInactivo) {
                $entityManager->remove($turnoDePacienteInactivo);
            }
        }
        $entityManager->flush();

        return $this->json("ok");
    }

}
