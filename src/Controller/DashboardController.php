<?php

namespace App\Controller;


use App\Entity\Cliente;
use App\Entity\Habitacion;
use App\Helpers\ExportToExcel;
use App\Repository\ClienteRepository;
use App\Repository\DoctorRepository;
use App\Repository\HabitacionRepository;
use App\Repository\ObraSocialRepository;
use DateTime;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\Request;
use PhpParser\Comment\Doc;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Routing\Annotation\Route;


/**
 * @Route("/dashboard")
 */
class DashboardController extends AbstractController
{
    /**
     * @Route("/", name="dashboard_index", methods={"GET"})
     */
    public function index(HabitacionRepository $habitacionRepository, ClienteRepository $clienteRepository, ObraSocialRepository $obraSocialRepository, DoctorRepository $doctorRepository): Response
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
     * @Route("/excel", name="to_excel", methods={"GET"})
     */

    public function toExcel(Request $request, KernelInterface $kernel, ObraSocialRepository $obraSocialRepository) {
        $habitacionesYpacientes = $this->getHabitacionesYpacientes();
        $cabeceras = $request->query->all();

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        $colums = ['A','B','C','D','E','F','G','H','I','J','K','L','M','N','O','P','Q','R','S','T','U','V','W','X','Y','Z'];

        $styleArrayHeaders = [
            'font' => [
                'bold' => true,
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                ],
            ],
        ];

        foreach ($cabeceras as $key => $value) {
            $sheet->setCellValue($colums[$key].'1', ucfirst($value));
            $sheet->getColumnDimension($colums[$key])->setAutoSize(true);
            $sheet->getStyle($colums[$key].'1')->applyFromArray($styleArrayHeaders);

        }
        $count = 2;
        $totalRows = count($habitacionesYpacientes);
        $osArray = $this->getOSarray($obraSocialRepository);

        $styleArrayBody = [
            'borders' => [
                'allBorders' => [
                    'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                ],
            ],
        ];
        foreach ($habitacionesYpacientes as $nombre => $habitacion) {
            if ( in_array('habitacion', $cabeceras) ) {
                $sheet->setCellValue($colums[0].$count, ucfirst($nombre . ' - ' . $habitacion['ocupadas'] . '/' . $habitacion['disponibles']));
                            }
            if ( in_array('paciente', $cabeceras) ) {
                foreach ($habitacion['cliente'] as $cliente) {
                    $sheet->setCellValue($colums[1].$count, ucfirst($cliente->getNombre() . " " . $cliente->getApellido() ));

                    if ( in_array('obraSocial', $cabeceras) ) {
                        $sheet->setCellValue($colums[2].$count, ucfirst($osArray[$cliente->getObraSocial()] ));
                    }

                    if ( in_array('patologia', $cabeceras) ) {
                        $sheet->setCellValue($colums[3].$count, ucfirst($cliente->getMotivoIng() ));
                    }

                    if ( in_array('dieta', $cabeceras) ) {
                        $sheet->setCellValue($colums[4].$count, ucfirst($cliente->getDieta() ));
                    }
                    if ( in_array('edad', $cabeceras) ) {
                        $sheet->setCellValue($colums[5].$count, ucfirst($cliente->getEdad() ));
                    }


                    if (count($habitacion['cliente']) > 1) {
                        $count ++;
                    }
                }
            }

            $count ++;
        }



        $writer = new Xlsx($spreadsheet);

        // Create a Temporary file in the system
        $fileName = 'Dashboard.xlsx';
        $temp_file = tempnam(sys_get_temp_dir(), $fileName);

        // Create the excel file in the tmp directory of the system
        $writer->save($temp_file);

        // Return the excel file as an attachment
        return $this->file($temp_file, $fileName, ResponseHeaderBag::DISPOSITION_INLINE);

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
        $habitaciones = $habitacionRepository->findAll();
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
