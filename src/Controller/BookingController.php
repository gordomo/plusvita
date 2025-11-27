<?php

namespace App\Controller;

use App\Entity\Booking;
use App\Entity\User;
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
        // Verificar permiso para ver la lista de turnos
        if (!$this->isGranted('agenda.manage') && !$this->isGranted('ROLE_ADMIN')) {
            throw $this->createAccessDeniedException('No tienes permisos para acceder a esta sección');
        }

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
            'Medico Clínico',
            'HidroTerapia motora',
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
        $docIdArrFiler = $request->query->get('doc_id');
        $cliFilter = $request->query->get('cli_id');
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
            'Medico Clínico',
            'HidroTerapia motora',
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

        // Verificar si el usuario tiene permiso para agendar turnos
        $canManageAgenda = $this->isGranted('agenda.manage') || $this->isGranted('ROLE_ADMIN');
        
        // Verificar si el usuario es doctor (para permitir editar sus propios turnos)
        $isDoctor = $user && $user->hasMedicalRole();

        return $this->render('booking/calendar.html.twig', [
            'clientes' => $clientes,
            'doctores' => $doctores,
            'contratos' => $contratos,
            'contratoFiltro' => $contrato,
            'ctrsArray' => $ctrsArray,
            'businessHours' => $businessHours,
            'docIdArrFiler' => $docIdArrFiler,
            'cliFilter' => $cliFilter,
            'canManageAgenda' => $canManageAgenda,
            'isDoctor' => $isDoctor
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
        // Verificar permiso para agendar turnos
        if (!$this->isGranted('agenda.manage') && !$this->isGranted('ROLE_ADMIN')) {
            throw $this->createAccessDeniedException('No tienes permisos para agendar turnos');
        }

        $booking = new Booking();
        $error = false;
        $yaTieneTurno = false;

        $user = $this->security->getUser();
        $booking->setUser($user);

        // Obtener fecha desde query string o request
        $dateParam = $request->query->get('date') ?? $request->request->get('date');
        $beginAt = !empty($dateParam) ? new \DateTime($dateParam) : new \DateTime();

        $minutes_to_add = 30;

        $endAt = !empty($dateParam) ? new \DateTime($dateParam) : new \DateTime();
        $endAt->add(new DateInterval('PT' . $minutes_to_add . 'M'));

        $booking->setBeginAt($beginAt);
        $booking->setEndAt($endAt);

        $ctr = !empty($request->get('ctr')) ? $request->get('ctr') : '';

        $form = $this->createForm(BookingType::class, $booking, ['ctr' => $ctr, 'isNew' => true]);
        $form->handleRequest($request);

        // Obtener todos los doctores disponibles y sus horarios para el JavaScript
        $allDoctors = $doctorRepository->findAll();
        $allDoctorsBusinessHours = [];
        foreach ($allDoctors as $doctor) {
            $allDoctorsBusinessHours[$doctor->getId()] = $doctor->getBusinessHours();
        }

        if ($form->isSubmitted() && $form->isValid()) {

            // El formulario devuelve un Doctor, pero Booking necesita un User
            // Buscar el User correspondiente al Doctor por email
            $doctorEntity = $booking->getDoctor();
            $doctorUser = null;
            $doctorData = null; // Para acceder a métodos de Doctor como getMaxCliTurno()
            
            if ($doctorEntity instanceof \App\Entity\Doctor) {
                // Buscar el User correspondiente
                $doctorUser = $this->getDoctrine()->getRepository(User::class)
                    ->findOneBy(['email' => $doctorEntity->getEmail()]);
                if (!$doctorUser) {
                    $this->addFlash('error', 'No se encontró el usuario correspondiente al profesional seleccionado.');
                    return $this->render('booking/new.html.twig', [
                        'booking' => $booking,
                        'form' => $form->createView(),
                        'error' => false,
                        'allDoctorsBusinessHours' => $allDoctorsBusinessHours,
                    ]);
                }
                $booking->setDoctor($doctorUser);
                // Guardar referencia al Doctor para acceder a sus métodos
                $doctorData = $doctorEntity;
            } else {
                // Si ya es un User, usarlo directamente y buscar el Doctor correspondiente
                $doctorUser = $doctorEntity;
                $doctorData = $this->getDoctrine()->getRepository(\App\Entity\Doctor::class)
                    ->createQueryBuilder('d')
                    ->where('d.email = :email')
                    ->setParameter('email', $doctorUser->getEmail())
                    ->getQuery()
                    ->getOneOrNullResult();
            }
            
            $doctor = $doctorUser; // Para usar en Booking
            $cliente = $booking->getCliente();
            $newBeginAt = !empty($booking->getBeginAt()) ? $booking->getBeginAt() : new \DateTime();
            $newEndAt = !empty($booking->getEndAt()) ? $booking->getEndAt() : new \DateTime();

            // Validar que la fecha/hora del turno no sea anterior a la fecha/hora actual
            $now = new \DateTime();
            if ($newBeginAt < $now) {
                $this->addFlash('error', 'No se puede crear un turno para una fecha/hora anterior a la actual. Por favor, seleccione una fecha y hora futura.');
                return $this->render('booking/new.html.twig', [
                    'booking' => $booking,
                    'form' => $form->createView(),
                    'error' => false,
                    'allDoctorsBusinessHours' => $allDoctorsBusinessHours,
                ]);
            }

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
                    $maxCliTurno = $doctorData ? $doctorData->getMaxCliTurno() : null;
                    if ( count($bookings) >= $maxCliTurno && $maxCliTurno != null || ($maxCliTurno == null ) ) {
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
                            $maxCliTurno = $doctorData ? $doctorData->getMaxCliTurno() : null;
                            if ( count($bookings) >= $maxCliTurno && $maxCliTurno != null || ($maxCliTurno == null ) ) {
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
                // Redirigir al calendario sin filtros después de crear el turno
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
            'allDoctorsBusinessHours' => $allDoctorsBusinessHours,
        ]);
    }

    /**
     * @Route("/{id}", name="booking_show", methods={"GET"})
     */
    public function show(Booking $booking): Response
    {
        $user = $this->getUser();
        if (!$user) {
            return $this->redirectToRoute('app_login');
        }
        
        // Verificar si el turno está asignado al usuario actual
        // Esto es más confiable que verificar roles específicos
        $isDoctorEditingOwnBooking = false;
        $doctorUser = $booking->getDoctor();
        
        if ($doctorUser) {
            $doctorId = $doctorUser->getId();
            $userId = $user->getId();
            
            // Si el turno está asignado al usuario actual, permitir editarlo
            // Comparar IDs como enteros para evitar problemas de tipo
            if ($doctorId && $userId && (int)$doctorId === (int)$userId) {
                $isDoctorEditingOwnBooking = true;
            }
        }
        
        // Verificar si el usuario tiene algún rol médico
        $isDoctor = $user->hasMedicalRole();
        
        // Si es un doctor viendo su propio turno, redirigir a editar
        if ($isDoctorEditingOwnBooking) {
            return $this->redirectToRoute('booking_edit', ['id' => $booking->getId()]);
        }
        
        // Si no tiene permisos de administración, no permitir ver turnos de otros
        if (!$this->isGranted('agenda.manage') && !$this->isGranted('ROLE_ADMIN')) {
            // Si es doctor pero no es su turno, redirigir al dashboard en lugar de lanzar excepción
            if ($isDoctor) {
                return $this->redirectToRoute('dashboard_index');
            }
            throw $this->createAccessDeniedException('No tienes permisos para ver este turno');
        }
        
        return $this->render('booking/show.html.twig', [
            'booking' => $booking,
        ]);
    }

    /**
     * @Route("/{id}/edit", name="booking_edit", methods={"GET","POST"})
     */
    public function edit(Request $request, Booking $booking, DoctorRepository $doctorRepository, BookingRepository $bookingRepository): Response
    {
        $user = $this->getUser();
        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        // Verificar si es un doctor editando su propio turno
        $isDoctorEditingOwnBooking = false;
        $doctorUser = $booking->getDoctor();
        if ($doctorUser && $doctorUser->getId() === $user->getId()) {
            $isDoctorEditingOwnBooking = true;
        }

        // Verificar permisos: admin/agenda.manage pueden editar cualquier turno, doctores solo sus propios
        if (!$isDoctorEditingOwnBooking && !$this->isGranted('agenda.manage') && !$this->isGranted('ROLE_ADMIN')) {
            throw $this->createAccessDeniedException('No tienes permisos para editar este turno');
        }

        // Obtener todos los doctores con sus business hours para el filtrado dinámico
        $allDoctors = $doctorRepository->findAll();
        $allDoctorsBusinessHours = [];
        foreach ($allDoctors as $doctor) {
            $allDoctorsBusinessHours[$doctor->getId()] = $doctor->getBusinessHours();
        }

        // Si es un doctor editando, solo permitir editar fecha/hora
        $form = $this->createForm(BookingType::class, $booking, [
            'doctor_edit' => $isDoctorEditingOwnBooking
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $doctor = $booking->getDoctor(); // Ya es un User
            
            // Si es un doctor editando, asegurar que no cambie el doctor ni el cliente
            if ($isDoctorEditingOwnBooking) {
                // Mantener el doctor y cliente originales
                $originalDoctor = $booking->getDoctor();
                $originalCliente = $booking->getCliente();
                // Los campos doctor y cliente no estarán en el formulario, así que están protegidos
            }
            
            // Buscar el Doctor correspondiente para acceder a getMaxCliTurno()
            $doctorData = $this->getDoctrine()->getRepository(\App\Entity\Doctor::class)
                ->createQueryBuilder('d')
                ->where('d.email = :email')
                ->setParameter('email', $doctor->getEmail())
                ->getQuery()
                ->getOneOrNullResult();
            
            $newBeginAt = $booking->getBeginAt();
            
            // Validar que la fecha/hora del turno no sea anterior a la fecha/hora actual
            $now = new \DateTime();
            if ($newBeginAt < $now) {
                $error = 'No se puede modificar un turno a una fecha/hora anterior a la actual. Por favor, seleccione una fecha y hora futura.';
            } else {
                // Excluir el turno actual del conteo
                $bookings = $bookingRepository->createQueryBuilder('b')
                    ->where('b.doctor = :doctor')
                    ->andWhere('b.beginAt = :beginAt')
                    ->andWhere('b.id != :currentId')
                    ->setParameter('doctor', $doctor)
                    ->setParameter('beginAt', $newBeginAt)
                    ->setParameter('currentId', $booking->getId())
                    ->getQuery()
                    ->getResult();
                
                $maxCliTurno = $doctorData ? $doctorData->getMaxCliTurno() : null;

                if (count($bookings) >= $maxCliTurno && $maxCliTurno != null) {
                    $error = 'El turno no puede ser movido a esa fecha/horario porque supera el número máximo de pacientes por turno que puede atender el profesional';
                } else {
                    $this->getDoctrine()->getManager()->flush();
                    // Redirigir al calendario general después de editar
                    return $this->redirectToRoute('booking_calendar');
                }
            }
        }

        return $this->render('booking/edit.html.twig', [
            'booking' => $booking,
            'form' => $form->createView(),
            'error' => $error ?? 0,
            'allDoctorsBusinessHours' => $allDoctorsBusinessHours,
            'isDoctorEdit' => $isDoctorEditingOwnBooking,
        ]);
    }

    /**
     * @Route("/{id}/{start}/{end}", name="booking_edit_ajax", methods={"GET","POST"})
     */
    public function ajaxEdit($id, $start, $end, BookingRepository $bookingRepository, DoctorRepository $doctorRepository): Response
    {
        $user = $this->getUser();
        if (!$user) {
            return new JsonResponse(['error' => true, 'message' => 'Debes estar autenticado para editar turnos']);
        }

        $error = false;
        $message = 'ok';
        try {
            $booking = $bookingRepository->find($id);
            if (!$booking) {
                return new JsonResponse(['error' => true, 'message' => 'Turno no encontrado']);
            }

            // Verificar si es un doctor editando su propio turno
            $isDoctorEditingOwnBooking = false;
            $doctorUser = $booking->getDoctor();
            if ($doctorUser && $doctorUser->getId() === $user->getId()) {
                $isDoctorEditingOwnBooking = true;
            }

            // Verificar permisos: admin/agenda.manage pueden editar cualquier turno, doctores solo sus propios
            if (!$isDoctorEditingOwnBooking && !$this->isGranted('agenda.manage') && !$this->isGranted('ROLE_ADMIN')) {
                return new JsonResponse(['error' => true, 'message' => 'No tienes permisos para editar este turno']);
            }

            $beginAt = new \DateTime(substr($start, 0, 33));
            $beginAt->modify('+3 hours');
            $endAt = new \DateTime(substr($end, 0, 33));
            $endAt->modify('+3 hours');
            
            // Validar que la fecha/hora del turno no sea anterior a la fecha/hora actual
            $now = new \DateTime();
            if ($beginAt < $now) {
                $error = true;
                $message = 'No se puede mover un turno a una fecha/hora anterior a la actual. Por favor, seleccione una fecha y hora futura.';
                return new JsonResponse(['error' => $error, 'message' => $message]);
            }
            
            $doctor = $booking->getDoctor(); // Already a User entity
            
            // Excluir el turno actual del conteo
            $bookings = $bookingRepository->createQueryBuilder('b')
                ->where('b.doctor = :doctor')
                ->andWhere('b.beginAt = :beginAt')
                ->andWhere('b.id != :currentId')
                ->setParameter('doctor', $doctor)
                ->setParameter('beginAt', $beginAt)
                ->setParameter('currentId', $booking->getId())
                ->getQuery()
                ->getResult();

            // Buscar el Doctor correspondiente para acceder a getMaxCliTurno()
            $doctorData = $this->getDoctrine()->getRepository(\App\Entity\Doctor::class)
                ->createQueryBuilder('d')
                ->where('d.email = :email')
                ->setParameter('email', $doctor->getEmail())
                ->getQuery()
                ->getOneOrNullResult();
            
            $maxCliTurno = $doctorData ? $doctorData->getMaxCliTurno() : null;

            if (count($bookings) >= $maxCliTurno && $maxCliTurno != null) {
                $error = true;
                $message = 'El turno no puede ser movido a esa fecha/horario porque supera el número máximo de pacientes por turno que puede atender el profesional';
                return new JsonResponse(['error' => $error, 'message' => $message]);
            }

            $booking->setBeginAt($beginAt);
            $booking->setEndAt($endAt);
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->persist($booking);
            $entityManager->flush();

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
     * @Route("/delete/multiple", name="booking_multi_delete", methods={"POST"})
     */
    public function multiDelete(Request $request, BookingRepository $bookingRepository): Response
    {
        $user = $this->getUser();
        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        $ids = $request->get('ids') ?? '';

        $entityManager = $this->getDoctrine()->getManager();

        foreach ($ids as $id) {
            $booking = $bookingRepository->find($id);

            if(!$booking->getCompletado()) {
                $entityManager->remove($booking);
            }
        }

        $entityManager->flush();

        return $this->json("ok");
    }

    private function getBusinessHours(array $doctores)
    {
        $businessHours = [];
        $diasMap = [
            1 => 'lunes',
            2 => 'martes',
            3 => 'miercoles',
            4 => 'jueves',
            5 => 'viernes',
            6 => 'sabado',
            7 => 'domingo',
        ];
        
        foreach ($doctores as $doctor) {
            $doctorsBusinessHours = $doctor->getBusinessHours();

            if (!empty($doctorsBusinessHours) && is_array($doctorsBusinessHours)) {
                foreach ($doctorsBusinessHours as $dayNum => $ranges) {
                    // Convertir a entero si viene como string
                    $dayNumInt = is_numeric($dayNum) ? (int)$dayNum : $dayNum;
                    
                    if (!isset($diasMap[$dayNumInt])) {
                        continue;
                    }
                    
                    $dayName = $diasMap[$dayNumInt];
                    
                    // Manejar formato antiguo: objeto con desde/hasta/ydesde/yhasta
                    if (is_array($ranges) && isset($ranges['desde']) && isset($ranges['hasta'])) {
                        // Formato antiguo: ['desde' => '08:00', 'hasta' => '18:00', 'ydesde' => '08:00', 'yhasta' => '18:00']
                        if (!isset($businessHours[$dayName])) {
                            $businessHours[$dayName] = [
                                'desde' => $ranges['desde'],
                                'hasta' => $ranges['hasta'],
                                'ydesde' => $ranges['ydesde'] ?? $ranges['desde'],
                                'yhasta' => $ranges['yhasta'] ?? $ranges['hasta'],
                            ];
                        } else {
                            // Si ya existe, expandir el rango para cubrir todos los doctores
                            // Comparar y tomar el inicio más temprano y el fin más tarde
                            $existingDesde = $businessHours[$dayName]['desde'];
                            $existingHasta = $businessHours[$dayName]['hasta'];
                            
                            // Convertir a minutos para comparar
                            [$existingDesdeHour, $existingDesdeMin] = explode(':', $existingDesde);
                            [$existingHastaHour, $existingHastaMin] = explode(':', $existingHasta);
                            [$newDesdeHour, $newDesdeMin] = explode(':', $ranges['desde']);
                            [$newHastaHour, $newHastaMin] = explode(':', $ranges['hasta']);
                            
                            $existingDesdeMinutes = (int)$existingDesdeHour * 60 + (int)$existingDesdeMin;
                            $existingHastaMinutes = (int)$existingHastaHour * 60 + (int)$existingHastaMin;
                            $newDesdeMinutes = (int)$newDesdeHour * 60 + (int)$newDesdeMin;
                            $newHastaMinutes = (int)$newHastaHour * 60 + (int)$newHastaMin;
                            
                            // Tomar el inicio más temprano
                            if ($newDesdeMinutes < $existingDesdeMinutes) {
                                $businessHours[$dayName]['desde'] = $ranges['desde'];
                            }
                            
                            // Tomar el fin más tarde
                            if ($newHastaMinutes > $existingHastaMinutes) {
                                $businessHours[$dayName]['hasta'] = $ranges['hasta'];
                            }
                            
                            // Manejar ydesde/yhasta (segundo rango)
                            if (isset($ranges['ydesde']) && isset($ranges['yhasta'])) {
                                if (!isset($businessHours[$dayName]['ydesde']) || 
                                    $ranges['ydesde'] < $businessHours[$dayName]['ydesde']) {
                                    $businessHours[$dayName]['ydesde'] = $ranges['ydesde'];
                                }
                                if (!isset($businessHours[$dayName]['yhasta']) || 
                                    $ranges['yhasta'] > $businessHours[$dayName]['yhasta']) {
                                    $businessHours[$dayName]['yhasta'] = $ranges['yhasta'];
                                }
                            }
                        }
                    } 
                    // Manejar formato nuevo (para compatibilidad): array de rangos con start/end
                    else if (is_array($ranges) && !empty($ranges) && isset($ranges[0])) {
                        // Formato nuevo: [['start' => '08:00', 'end' => '16:00'], ['start' => '18:00', 'end' => '20:00']]
                        // Convertir a formato antiguo
                        $earliestStart = null;
                        $latestEnd = null;
                        $secondEarliestStart = null;
                        $secondLatestEnd = null;
                        
                        foreach ($ranges as $range) {
                            if (!isset($range['start']) || !isset($range['end'])) {
                                continue;
                            }
                            
                            $start = $range['start'];
                            $end = $range['end'];
                            
                            // Convertir a minutos para comparar
                            [$startHour, $startMin] = explode(':', $start);
                            [$endHour, $endMin] = explode(':', $end);
                            $startMinutes = (int)$startHour * 60 + (int)$startMin;
                            $endMinutes = (int)$endHour * 60 + (int)$endMin;
                            
                            // Encontrar el inicio más temprano
                            if ($earliestStart === null || $startMinutes < $earliestStart['minutes']) {
                                if ($earliestStart !== null) {
                                    $secondEarliestStart = $earliestStart;
                                }
                                $earliestStart = ['time' => $start, 'minutes' => $startMinutes];
                            } elseif ($secondEarliestStart === null || $startMinutes < $secondEarliestStart['minutes']) {
                                $secondEarliestStart = ['time' => $start, 'minutes' => $startMinutes];
                            }
                            
                            // Encontrar el fin más tarde
                            if ($latestEnd === null || $endMinutes > $latestEnd['minutes']) {
                                if ($latestEnd !== null) {
                                    $secondLatestEnd = $latestEnd;
                                }
                                $latestEnd = ['time' => $end, 'minutes' => $endMinutes];
                            } elseif ($secondLatestEnd === null || $endMinutes > $secondLatestEnd['minutes']) {
                                $secondLatestEnd = ['time' => $end, 'minutes' => $endMinutes];
                            }
                        }
                        
                        if ($earliestStart === null || $latestEnd === null) {
                            continue;
                        }
                        
                        // Inicializar el día si no existe
                        if (!isset($businessHours[$dayName])) {
                            $businessHours[$dayName] = [
                                'desde' => $earliestStart['time'],
                                'hasta' => $latestEnd['time'],
                                'ydesde' => $secondEarliestStart ? $secondEarliestStart['time'] : $earliestStart['time'],
                                'yhasta' => $secondLatestEnd ? $secondLatestEnd['time'] : $latestEnd['time'],
                            ];
                        } else {
                            // Comparar y actualizar con los rangos más amplios
                            [$currentDesdeHour, $currentDesdeMin] = explode(':', $businessHours[$dayName]['desde']);
                            [$currentHastaHour, $currentHastaMin] = explode(':', $businessHours[$dayName]['hasta']);
                            $currentDesdeMinutes = (int)$currentDesdeHour * 60 + (int)$currentDesdeMin;
                            $currentHastaMinutes = (int)$currentHastaHour * 60 + (int)$currentHastaMin;
                            
                            // Actualizar 'desde' si encontramos uno más temprano
                            if ($earliestStart['minutes'] < $currentDesdeMinutes) {
                                $businessHours[$dayName]['desde'] = $earliestStart['time'];
                            }
                            
                            // Actualizar 'hasta' si encontramos uno más tarde
                            if ($latestEnd['minutes'] > $currentHastaMinutes) {
                                $businessHours[$dayName]['hasta'] = $latestEnd['time'];
                            }
                            
                            // Actualizar 'ydesde' y 'yhasta' si hay un segundo rango
                            if ($secondEarliestStart) {
                                if (!isset($businessHours[$dayName]['ydesde'])) {
                                    $businessHours[$dayName]['ydesde'] = $secondEarliestStart['time'];
                                } else {
                                    [$currentYDesdeHour, $currentYDesdeMin] = explode(':', $businessHours[$dayName]['ydesde']);
                                    $currentYDesdeMinutes = (int)$currentYDesdeHour * 60 + (int)$currentYDesdeMin;
                                    if ($secondEarliestStart['minutes'] < $currentYDesdeMinutes) {
                                        $businessHours[$dayName]['ydesde'] = $secondEarliestStart['time'];
                                    }
                                }
                            }
                            
                            if ($secondLatestEnd) {
                                if (!isset($businessHours[$dayName]['yhasta'])) {
                                    $businessHours[$dayName]['yhasta'] = $secondLatestEnd['time'];
                                } else {
                                    [$currentYHastaHour, $currentYHastaMin] = explode(':', $businessHours[$dayName]['yhasta']);
                                    $currentYHastaMinutes = (int)$currentYHastaHour * 60 + (int)$currentYHastaMin;
                                    if ($secondLatestEnd['minutes'] > $currentYHastaMinutes) {
                                        $businessHours[$dayName]['yhasta'] = $secondLatestEnd['time'];
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }

        return $businessHours;
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
