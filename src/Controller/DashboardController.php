<?php

namespace App\Controller;


use App\Entity\Cliente;
use App\Entity\Habitacion;
use App\Controller\ExportToExcel;
use App\Entity\HistoriaPaciente;
use App\Repository\ClienteRepository;
use App\Repository\DoctorRepository;
use App\Repository\HabitacionRepository;
use App\Repository\HistoriaHabitacionesRepository;
use App\Repository\HistoriaPacienteRepository;
use App\Repository\ObraSocialRepository;
use DateTime;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use PhpParser\Comment\Doc;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Mime\FileinfoMimeTypeGuesser;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Encoder\XmlEncoder;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Serializer;


/**
 * @Route("/dashboard")
 */
class DashboardController extends AbstractController
{
    /**
     * @Route("/", name="dashboard_index", methods={"GET"})
     */
    public function index(Request $request, HabitacionRepository $habitacionRepository, ClienteRepository $clienteRepository, ObraSocialRepository $obraSocialRepository, DoctorRepository $doctorRepository): Response
    {
        $isDoctor = $this->isDoctor();

        $habitacionesYpacientes = $this->getHabitacionesYpacientes();

        $osArray = $this->getOSarray($obraSocialRepository);

        $isContratosVencidos = $this->hayContratosVencidos($doctorRepository);
        $vencenEsteMes = $this->hayVencenEsteMes($doctorRepository);

        $colorCampana = 'grey';

        if ($isContratosVencidos) {
            $colorCampana = 'red';
        }

        return $this->render('dashboard.html.twig',
            [
                'dashboardActive' => 'active',
                'isDoctor' => $isDoctor,
                'habitacionesYpacientes' => $habitacionesYpacientes,
                'obrasSociales' => $osArray,
                'paginaImprimible' => !$isDoctor,
                'hayContratosVencidos' => $isContratosVencidos,
                'hayVencenEsteMes' => $vencenEsteMes,
                'colorCampana' => $colorCampana
            ]);
    }

    /**
     * @Route("/get/pacientes", name="dashboard_index_filtro_cantidad_pacientes", methods={"POST", "GET"})
     */
    public function getPacientesFromTo(Request $request, ObraSocialRepository $obraSocialRepository, HistoriaHabitacionesRepository $historiaHabitacionesRepository, ClienteRepository $clienteRepository) {

        $encoders = [new XmlEncoder(), new JsonEncoder()];
        $normalizers = [new ObjectNormalizer()];

        $serializer = new Serializer($normalizers, $encoders);

        $from = $request->get('from');
        $to = $request->get('to');

        $historias = $historiaHabitacionesRepository->findByDate($from,  $to);

        $obrasSociales = $this->getOSarray($obraSocialRepository);

        $arrHistorias = [];

        foreach ( $historias as $historia ) {
            $cliente = $historia->getCliente();
            $arrHistorias[] = [
                'nombreCliente' => $cliente->getNombre() . ' ' . $cliente->getApellido(),
                'obraSocial' => $obrasSociales[$cliente->getObraSocial()] ?? 'Sin OS',
                'fecha' => $historia->getFecha(),
                'habitacion' => $historia->getHabitacion()->getNombre(),
                'cama' => $historia->getNCama(),
            ];
        }

        return new JsonResponse($arrHistorias);

    }



    /**
     * @Route("/excel", name="to_excel", methods={"POST"})
     */

    public function toExcel(Request $request, RouterInterface $router) {
        return ExportToExcel::toExcel($request->get('html'), $router, $request->request->get('tituloExcel'));
    }

    private function isDoctor()
    {
        $isDoctor = false;
        $user = $this->getUser();

        $modalidad = 'sinModalidad';
        if (is_callable([$user, 'getModalidad'])) {
            $modalidad = $user->getModalidad()[0];
        }

        if( in_array($modalidad, $this->getModalidades(2)) ||
            in_array($modalidad, $this->getModalidades(3)) ||
            in_array($modalidad, $this->getModalidades(4))) {
            $isDoctor = true;
        }

        return $isDoctor;

    }

    private function getModalidades(int $contrato)
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
            'Kinesiologo motora',
            'Kinesiología respiratoria',
            'HidroTerapia motora',
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

        $modalidades = [
            1 => array_combine($empleado, $empleado),
            2 => array_combine($directo, $directo),
            3 => array_combine($prestacion,$prestacion),
            4 => array_combine($sinContrato, $sinContrato)
        ];
        return $modalidades[$contrato];
    }

    private function getHabitacionesYpacientes()
    {
        $habitacionRepository = $this->getDoctrine()->getRepository(Habitacion::class);
        $clienteRepository = $this->getDoctrine()->getRepository(Cliente::class);
        $habitaciones = $habitacionRepository->findAllInNameOrder();
        $arrayClienteHabitaciones = [];
        foreach ($habitaciones as $habitacion) {
            $cliente = $clienteRepository->findActivos(new \DateTime(), '', $habitacion);
            $data = [
                'cliente' => $cliente,
                'ocupadas' => count($habitacion->getCamasOcupadas()),
                'disponibles' => $habitacion->getCamasDisponibles(),
            ];
            if ($cliente) $arrayClienteHabitaciones['habitación ' . $habitacion->getNombre()] = $data;
        }

        return $arrayClienteHabitaciones;
    }

    private function hayContratosVencidos(DoctorRepository $doctorRepository)
    {
        return count($doctorRepository->findAllVencidos());
    }

    private function hayVencenEsteMes(DoctorRepository $doctorRepository)
    {
        return count($doctorRepository->findAllVencenEsteMes());
    }

    private function getOSarray($obraSocialRepository)
    {
        $obrasSociales = $obraSocialRepository->findAll();
        $osArray = [];
        foreach ($obrasSociales as $os) {
            $osArray[$os->getId()] = $os->getNombre();
        }

        return $osArray;
    }
}
