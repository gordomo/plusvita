<?php

namespace App\Controller;

use App\Entity\Doctor;
use App\Form\DoctorType;
use App\Repository\BookingRepository;
use App\Repository\ClienteRepository;
use App\Repository\DoctorRepository;
use App\Repository\ObraSocialRepository;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\String\Slugger\SluggerInterface;


/**
 * @Route("/staff")
 */
class DoctorController extends AbstractController
{
    private $colors = [
        '#F0F8FF',
        '#FAEBD7',
        '#00FFFF',
        '#7FFFD4',
        '#F0FFFF',
        '#F5F5DC',
        '#FFE4C4',
        '#FFEBCD',
        '#0000FF',
        '#8A2BE2',
        '#A52A2A',
        '#DEB887',
        '#5F9EA0',
        '#7FFF00',
        '#D2691E',
        '#FF7F50',
        '#6495ED',
        '#FFF8DC',
        '#DC143C',
        '#00FFFF',
        '#00008B',
        '#008B8B',
        '#B8860B',
        '#A9A9A9',
        '#006400',
        '#A9A9A9',
        '#BDB76B',
        '#8B008B',
        '#556B2F',
        '#FF8C00',
        '#9932CC',
        '#8B0000',
        '#E9967A',
        '#8FBC8F',
        '#483D8B',
        '#2F4F4F',
        '#2F4F4F',
        '#00CED1',
        '#9400D3',
        '#FF1493',
        '#00BFFF',
        '#696969',
        '#696969',
        '#1E90FF',
        '#B22222',
        '#FFFAF0',
        '#228B22',
        '#FF00FF',
        '#DCDCDC',
        '#F8F8FF',
        '#FFD700',
        '#DAA520',
        '#808080',
        '#008000',
        '#ADFF2F',
        '#808080',
        '#F0FFF0',
        '#FF69B4',
        '#CD5C5C',
        '#4B0082',
        '#FFFFF0',
        '#F0E68C',
        '#E6E6FA',
        '#FFF0F5',
        '#7CFC00',
        '#FFFACD',
        '#ADD8E6',
        '#F08080',
        '#E0FFFF',
        '#FAFAD2',
        '#D3D3D3',
        '#90EE90',
        '#D3D3D3',
        '#FFB6C1',
        '#FFA07A',
        '#20B2AA',
        '#87CEFA',
        '#778899',
        '#778899',
        '#B0C4DE',
        '#FFFFE0',
        '#00FF00',
        '#32CD32',
        '#FAF0E6',
        '#FF00FF',
        '#800000',
        '#66CDAA',
        '#0000CD',
        '#BA55D3',
        '#9370D8',
        '#3CB371',
        '#7B68EE',
        '#00FA9A',
        '#48D1CC',
        '#C71585',
        '#191970',
        '#F5FFFA',
        '#FFE4E1',
        '#FFE4B5',
        '#FFDEAD',
        '#000080',
        '#FDF5E6',
        '#808000',
        '#6B8E23',
        '#FFA500',
        '#FF4500',
        '#DA70D6',
        '#EEE8AA',
        '#98FB98',
        '#AFEEEE',
        '#D87093',
        '#FFEFD5',
        '#FFDAB9',
        '#CD853F',
        '#FFC0CB',
        '#DDA0DD',
        '#B0E0E6',
        '#800080',
        '#FF0000',
        '#BC8F8F',
        '#4169E1',
        '#8B4513',
        '#FA8072',
        '#F4A460',
        '#2E8B57',
        '#FFF5EE',
        '#A0522D',
        '#C0C0C0',
        '#87CEEB',
        '#6A5ACD',
        '#708090',
        '#708090',
        '#FFFAFA',
        '#00FF7F',
        '#4682B4',
        '#D2B48C',
        '#008080',
        '#D8BFD8',
        '#FF6347',
        '#40E0D0',
        '#EE82EE',
        '#F5DEB3',
        '#FFFFFF',
        '#F5F5F5',
        '#FFFF00',
        '#9ACD32'
    ];

    private $dias =  [1 => 'lunes', 2 => 'martes', 3 => 'miercoles', 4 => 'jueves', 5 => 'viernes', 6 => 'sabado'];

    /**
     * @Route("/", name="doctor_index", methods={"GET"})
     */
    public function index(Request $request, DoctorRepository $doctorRepository): Response
    {
        $empleado = [
            'Mucamo/a',
            'Enfermero/a',
            'Auxiliar de enfermeria',
            'Asistente de enfermeria',
            'Mantenimiento',
            'Cocinero',
            'Ayudante de cocina',
            'Administrativo',
            'Recepcionista',
            'Coordinador de pisos',
            'Coordinador general',
            'Coordinador de enfermeria'
        ];
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
        $contratos = ['empleado'=> $empleado, 'directo' => $directo, 'prestacion' => $prestacion, 'sinContrato' => $sinContrato];

        $ctrs = $request->query->get('ctr');
        $ctrsArray = explode(',', $ctrs);

        $conContratosVencidos = $request->query->get('vencidos', '0');

        if(!empty($ctrs)) {
            $doctores = $doctorRepository->findByContratos($ctrsArray, $conContratosVencidos);
        } else if ($conContratosVencidos) {
            $doctores = $doctorRepository->findAllVencidos();
        } else {
            $doctores = $doctorRepository->findAll();
        }

        return $this->render('doctor/index.html.twig', [
            'doctors' => $doctores,
            'contratos' => $contratos,
            'ctrsArray' => $ctrsArray,
            'paginaImprimible' => true,
            'conContratosVencidos' => $conContratosVencidos,
            
        ]);
    }

    /**
     * @Route("/tipo-select", name="staff_tipo_select")
     */
    public function getModalidadesSelect(Request $request)
    {
        $doctor = new Doctor();
        $doctor->setTipo($request->query->get('tipo'));
        $form = $this->createForm(DoctorType::class, $doctor);
        if (!$form->has('modalidad')) {
            return new Response(null, 204);
        }

        return $this->render('doctor/_modalidad.html.twig', [
            'form' => $form->createView(),
        ]);

    }

    /**
     * @Route("/check-email/", name="staff_check_email")
     */
    public function checkEmail(Request $request, DoctorRepository $doctorRepository, ClienteRepository $clienteRepository, UserRepository $userRepository)
    {
        $libre = true;
        $message = '';
        $email = $request->query->get('email');
        $id = $request->query->get('id');

        $doctor = $doctorRepository->findBy(['email' => $email], ['id'=>'DESC'], 1);
        $cliente = $clienteRepository->findBy(['email' => $email], ['id'=>'DESC'], 1);
        $user = $userRepository->findBy(['email' => $email], ['id'=>'DESC'], 1);

        if( (count($doctor) > 0 && $doctor[0]->getId() != $id) ||
            (count($cliente) && $cliente[0]->getId() != $id) ||
            (count($user) && $user[0]->getId() != $id)) {
            $libre = false;
            $message = 'el email ingresado se encuentra en uso';
        }

        return new JsonResponse(['libre' => $libre, 'message' => $message]);

    }

    /**
     * @Route("/new", name="doctor_new", methods={"GET","POST"})
     */
    public function new(Request $request,  SluggerInterface $slugger, DoctorRepository $doctorRepository): Response
    {
        $doctor = new Doctor();
        $doctor->setRoles([]);
        $doctor->setInicioContrato(new \DateTime());
        //dd($this->colors);
        $coloresEnUso = $doctorRepository->findColoresEnUso();
        foreach($coloresEnUso as $colorUsado) {
            if (($key = array_search($colorUsado['color'], $this->colors)) !== false) {
                unset($this->colors[$key]);
            }
        }

        if( count($this->colors) === 0 ) $this->colors[0] = '#2196f3';
        $doctor->setColor($this->colors[array_key_first($this->colors)]);

        $form = $this->createForm(DoctorType::class, $doctor, ['is_new' => true, 'allow_extra_fields' =>true, 'colors' => $this->colors]);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            $firmaPdfFile = $form->get('firmaPdf')->getData();
            if ($firmaPdfFile) {
                $originalFilename = pathinfo($firmaPdfFile->getClientOriginalName(), PATHINFO_FILENAME);
                $form->get('dni');
                $safeFilename = $slugger->slug($originalFilename);
                $newFilename = $safeFilename.'-'.uniqid().'.'.$firmaPdfFile->guessExtension();

                try {
                    $firmaPdfFile->move(
                        $this->getParameter('firmas_directory'),
                        $newFilename
                    );
                } catch (FileException $e) {
                    // ... handle exception if something happens during file upload
                }

                // updates the 'brochureFilename' property to store the PDF file name
                // instead of its contents
                $doctor->setFirma($newFilename);
            }

            $horarios = [];

            foreach ($this->dias as $key => $dia) {
                $desde = $form->get($dia.'desde')->getData() ?? '08:00';
                $ydesde = $form->get('y'.$dia.'desde')->getData() ?? $desde;

                $hasta = $form->get($dia.'hasta')->getData() ?? '18:00';
                $yhasta = $form->get('y'.$dia.'hasta')->getData() ?? $hasta;

                $horarios[$key] = ['desde' => $desde, 'hasta' => $hasta, 'ydesde' => $ydesde, 'yhasta' => $yhasta];
            }

            $doctor->setBusinessHours($horarios);


            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->persist($doctor);
            $entityManager->flush();

            return $this->redirectToRoute('doctor_index');
        }

        return $this->render('doctor/new.html.twig', [
            'doctor' => $doctor,
            'form' => $form->createView(),

        ]);
    }

    /**
     * @Route("/{id}", name="doctor_show", methods={"GET"})
     */
    public function show(Doctor $doctor): Response
    {
        return $this->render('doctor/show.html.twig', [
            'doctor' => $doctor,
            
        ]);
    }

    /**
     * @Route("/{id}/edit", name="doctor_edit", methods={"GET","POST"})
     */
    public function edit(Request $request, Doctor $doctor, SluggerInterface $slugger, DoctorRepository $doctorRepository): Response
    {
        $coloresEnUso = $doctorRepository->findColoresEnUso();
        foreach($coloresEnUso as $colorUsado) {
            if (($key = array_search($colorUsado['color'], $this->colors)) !== false) {
                unset($this->colors[$key]);
            }
        }
        if( count($this->colors) === 0 ) $this->colors[0] = '#2196f3';

        $form = $this->createForm(DoctorType::class, $doctor, ['is_new' => false, 'allow_extra_fields' =>true, 'colors' => $this->colors]);
        $dias =  $this->dias;
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $firmaPdfFile = $form->get('firmaPdf')->getData();
            if ($firmaPdfFile) {
                $originalFilename = pathinfo($firmaPdfFile->getClientOriginalName(), PATHINFO_FILENAME);
                $form->get('dni');
                $safeFilename = $slugger->slug($originalFilename);

                $newFilename = $safeFilename.'-'.uniqid().'.'.$firmaPdfFile->guessExtension();

                try {
                    $firmaPdfFile->move(
                        $this->getParameter('firmas_directory'),
                        $newFilename
                    );
                } catch (FileException $e) {
                    // ... handle exception if something happens during file upload
                }

                // updates the 'brochureFilename' property to store the PDF file name
                // instead of its contents
                $filesystem = new Filesystem();
                if(!empty($doctor->getFirma())) {
                    if ($filesystem->exists($this->getParameter('firmas_directory').'/'.$doctor->getFirma())) {
                        $filesystem->remove($this->getParameter('firmas_directory').'/'.$doctor->getFirma());
                    }
                }
                $doctor->setFirma($newFilename);
            }



            $horarios = [];


            foreach ($dias as $key => $dia) {
                $desde = $form->get($dia.'desde')->getData() ?? 0;
                $ydesde = $form->get('y'.$dia.'desde')->getData() ?? $desde;

                $hasta = $form->get($dia.'hasta')->getData() ?? 0;
                $yhasta = $form->get('y'.$dia.'hasta')->getData() ?? $hasta;

                if($desde != 0 && $hasta != 0) {
                    $horarios[$key] = ['desde' => $desde, 'hasta' => $hasta, 'ydesde' => $ydesde, 'yhasta' => $yhasta];
                }

            }

            $doctor->setBusinessHours($horarios);

            $this->getDoctrine()->getManager()->flush();

            return $this->redirectToRoute('doctor_index');
        }
        $businessHours = [];
        $doctorActualBusinessHours = $doctor->getBusinessHours();
        foreach ($dias as $key => $dia) {
            if (isset($doctorActualBusinessHours[$key])) {
                $businessHours[$dia] = $doctorActualBusinessHours[$key];
            }
        }

        return $this->render('doctor/edit.html.twig', [
            'doctor' => $doctor,
            'form' => $form->createView(),
            'title' => 'Editar:' . $doctor->getNombre() . ' ' . $doctor->getApellido(),
            'businessHours' => $businessHours,
        ]);
    }

    /**
     * @Route("/{id}/egreso", name="staff_egreso", methods={"GET","POST"})
     */
    public function egreso(Request $request, Doctor $doctor): Response
    {
        $form = $this->createForm(DoctorType::class, $doctor, ['is_new' => false, 'egreso' => true]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $doctor->setColor('none');
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->persist($doctor);
            $entityManager->flush();
            return $this->redirectToRoute('doctor_index');
        }

        return $this->render('doctor/edit.html.twig', [
            'doctor' => $doctor,
            'form' => $form->createView(),
            'title' => 'Egreso para:' . $doctor->getNombre() . ' ' . $doctor->getApellido(),
            'businessHours' => []
        ]);
    }

    /**
     * @Route("/{id}", name="doctor_delete", methods={"DELETE"})
     */
    public function delete(Request $request, Doctor $doctor): Response
    {
        if ($this->isCsrfTokenValid('delete'.$doctor->getId(), $request->request->get('_token'))) {
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->remove($doctor);
            $entityManager->flush();
        }

        return $this->redirectToRoute('doctor_index');
    }

    /**
     * @Route("/doctor/agenda/{periodo}/", name="doctor_agenda", methods={"GET"})
     */
    public function agenda(Request $request, BookingRepository $bookingRepository, ClienteRepository $clienteRepository, ObraSocialRepository $obraSocialRepository, $periodo)
    {
        $user = $this->getUser();
        if (!$user) {
           return $this->redirectToRoute('app_login');
        }
        $nombreInput = $request->query->get('nombreInput') ?? '';
        $desde = $request->query->get('desde') ?? '';
        $hasta = $request->query->get('hasta') ?? '';
        $obraSocialSelected = $request->query->get('obraSocial') ?? '';

        $from = '';
        $to = '';

        if($desde != '') {
            $from = (new \DateTime($desde));
        }
        if($hasta != '') {
            $to = (new \DateTime($hasta));
        }

        $clientes = $clienteRepository->findByNombreYobraSocial($nombreInput, $obraSocialSelected);

        $dia = new \DateTime();
        if ($obraSocialSelected != 0 && count($clientes) == 0) {
            $turnos = [];
        } else {
            $turnos = $bookingRepository->turnosParaAgenda($user, $dia, $periodo, $clientes, $from, $to);
        }

        $obrasSociales = $obraSocialRepository->findAll();
        $obrasSocialesArray = [];

        foreach ($obrasSociales as $obrasSocial) {
            $obrasSocialesArray[$obrasSocial->getId()] = $obrasSocial->getNombre();
        }

        return $this->render('doctor/agenda.html.twig', [
            'today' => $turnos,
            'nombreInput' => $nombreInput,
            'periodo' => $periodo,
            'desde' => $desde,
            'hasta' => $hasta,
            'obraSocialSelected' => $obraSocialSelected,
            'obrasSociales' => $obrasSocialesArray,
            'paginaImprimible' => true,
        ]);

    }

    /**
     * @Route("/doctor/agenda/{periodo}/{turnoId}/{completado}", name="doctor_agenda_update_turno", methods={"GET"})
     */
    public function updateTurno(Request $request, BookingRepository $bookingRepository, ClienteRepository $clienteRepository, ObraSocialRepository $obraSocialRepository, $periodo, $turnoId, $completado) {
        $user = $this->getUser();

        if($completado || in_array('ROLE_ADMIN', $user->getRoles())) {
            $book = $bookingRepository->find($turnoId);
            $book->setCompletado($completado);
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->persist($book);
            $entityManager->flush();
        }

        return $this->redirectToRoute('doctor_agenda', ['periodo' => $periodo]);

    }
}
