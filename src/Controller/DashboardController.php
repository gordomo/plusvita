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
        $isEnfermero = $this->isEnfermero();

        $user = $this->getUser();
        $modalidad = 'Sin Modalidad - Consulte al Administrador';
        if (is_callable([$user, 'getModalidad']) && !empty($user->getModalidad()) ) {
            $modalidad = $user->getModalidad()[0];
        }
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
                'isEnfermero' => $isEnfermero,
                'habitacionesYpacientes' => $habitacionesYpacientes,
                'obrasSociales' => $osArray,
                'paginaImprimible' => !$isDoctor && !$isEnfermero,
                'hayContratosVencidos' => $isContratosVencidos,
                'hayVencenEsteMes' => $vencenEsteMes,
                'colorCampana' => $colorCampana,
                'modalidad' => $modalidad,
                'habitacionRepository' => $habitacionRepository
            ]);
    }

    /**
     * @Route("/get/pacientes", name="dashboard_index_filtro_cantidad_pacientes", methods={"POST", "GET"})
     */
    public function getPacientesFromTo(Request $request, ObraSocialRepository $obraSocialRepository, HistoriaHabitacionesRepository $historiaHabitacionesRepository) {

        $from = $request->get('from', '2000/12/01');
        $to = $request->get('to', '2121/12/31');

        $fechaDesde = \DateTime::createFromFormat("d/m/Y", $from);
        $from = date("Y-m-d", strtotime($fechaDesde->format('Y/m/d')));


        $fechaHasta = \DateTime::createFromFormat("d/m/Y", $to);
        $to = date("Y-m-d", strtotime($fechaHasta->format('Y/m/d')));

        $historias = $historiaHabitacionesRepository->findByDate($from,  $to);

        $obrasSociales = $this->getOSarray($obraSocialRepository);

        $arrHistorias = [];

        $dateFrom = new \DateTime($from);
        $dateTo = new \DateTime($to);

        $arrHistorias['clientes'] = [];

        foreach ( $historias as $historia ) {
            $cliente = $historia->getCliente();
            if ( (!empty($cliente->getFEgreso()) && $cliente->getFEgreso() >= $historia->getFecha()) or (empty($cliente->getFEgreso())) ) {
                try {
                    $docReferente = $cliente->getDocReferente();
                } catch (\EntityNotFoundException $e) {
                    $docReferente = [];
                }
                foreach ($docReferente as $doc) {
                    if (isset ($arrHistorias['docReferentes'][$historia->getFecha()->format("Y-m-d")][$doc->getNombreApellido()])) {
                        $arrHistorias['docReferentes'][$historia->getFecha()->format("Y-m-d")][$doc->getNombreApellido()] = $arrHistorias['docReferentes'][$historia->getFecha()->format("Y-m-d")][$doc->getNombreApellido()] + 1;
                    } else {
                        $arrHistorias['docReferentes'][$historia->getFecha()->format("Y-m-d")][$doc->getNombreApellido()] = 1;
                    }
                }

                $arrHistoria = ['habitacion' => $historia->getHabitacion()->getNombre(), 'cama' => $historia->getNCama()];
                if ($historia->getFecha()->format("Y-m-d") !== $historia->getFecha()->format("Y-m-d") && empty($arrHistorias['clientes'][$cliente->getNombre() . ' ' . $cliente->getApellido()][$cliente->getObraSocial()->getNombre()][$historia->getFecha()->format("Y-m-d")])) {
                    $arrHistoria = [];
                }

                $arrHistorias['clientes'][$cliente->getNombre() . ' ' . $cliente->getApellido()][$cliente->getObraSocial()->getNombre()][$historia->getFecha()->format("Y-m-d")] = $arrHistoria;
                $arrHistorias['totales'][$historia->getFecha()->format("Y-m-d")] = $historiaHabitacionesRepository->countByDate($historia->getFecha()->format("Y-m-d"));   
            }
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
        if (is_callable([$user, 'getModalidad']) && !empty($user->getModalidad()) ) {
            $modalidad = $user->getModalidad()[0];
        }

        if( in_array($modalidad, $this->getModalidades(2)) ||
            in_array($modalidad, $this->getModalidades(3)) ||
            in_array($modalidad, $this->getModalidades(4))) {
            $isDoctor = true;
        }

        return $isDoctor;

    }

    private function isEnfermero()
    {
        $isEnfermero = false;
        $user = $this->getUser();

        $modalidad = 'sinModalidad';
        if (is_callable([$user, 'getModalidad']) && !empty($user->getModalidad()) ) {
            $modalidad = $user->getModalidad()[0];
        }

        if( in_array($modalidad, $this->getModalidadesEnfermeria())) {

            $isEnfermero = true;
        }

        return $isEnfermero;

    }

    private function getModalidadesEnfermeria() {
        return ['Enfermero/a', 'Auxiliar de enfermeria', 'Asistente de enfermeria'];
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
            'Medico Clínico',
            'HidroTerapia motora',
            'Kinesiologo motora',
            'Kinesiología respiratoria',
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

        $clientesConHabitacion = $clienteRepository->findClienteConHabitacion();
        $data = [];

        foreach($clientesConHabitacion as $cliente) {
            $habitacion = $habitacionRepository->find($cliente->getHabitacion());
            $data[$habitacion->getId()]['clientes'][] = $cliente;
            $data[$habitacion->getId()]['totales'] = $habitacion->getCamasDisponibles();
            $data[$habitacion->getId()]['disponibles'] = $cliente->getHabPrivada() ? 0 : isset($data[$habitacion->getId()]['disponibles']) ? $data[$habitacion->getId()]['disponibles'] - 1 : $habitacion->getCamasDisponibles() - 1; 
        }

        return $data;
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
