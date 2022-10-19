<?php

namespace App\Controller;

use App\Repository\BookingRepository;
use App\Repository\ClienteRepository;
use App\Repository\DoctorRepository;
use App\Repository\EvolucionRepository;
use App\Repository\ObraSocialRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;


/**
 * @Route("/liquidaciones")
 */
class LiquidacionesController extends AbstractController
{
    /**
     * @Route("/", name="liquidaciones_index", methods={"GET"})
     */
    public function index(): Response
    {
        return $this->render('liquidaciones/index.html.twig');
    }
    /**
     * @Route("/profesionales", name="profesionales_index", methods={"GET"})
     */
    public function profesionales(Request $request, DoctorRepository $doctorRepository): Response
    {
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
            'Kinesiologo motora ',
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
        $contratosParaBusqueda = array_merge($directo, $prestacion, $sinContrato);
        $contratosParaVista = ['directo' => $directo, 'prestacion' => $prestacion, 'sinContrato' => $sinContrato];

        $ctrs = $request->query->get('ctr');
        $ctrsArray = explode(',', $ctrs);


        if(!empty($ctrs)) {
            $profesionales = $doctorRepository->findByContratos($ctrsArray, false);
        } else {
            $profesionales = $doctorRepository->findByContratos($contratosParaBusqueda, false);
        }


        return $this->render('liquidaciones/profesionales.html.twig', [
            'doctors' => $profesionales,
            'contratos' => $contratosParaVista,
            'ctrsArray' => $ctrsArray
        ]);
    }

    /**
     * @Route("/profesional/{id}", name="liquidar", methods={"GET"})
     * @param $id
     * @param DoctorRepository $doctorRepository
     * @param BookingRepository $bookingRepository
     * @param ObraSocialRepository $obraSocialRepository
     * @param Request $request
     * @return Response
     * @throws \Exception
     */
    public function liquidar($id, DoctorRepository $doctorRepository, BookingRepository $bookingRepository, ObraSocialRepository $obraSocialRepository, ClienteRepository $clienteRepository, Request $request, EvolucionRepository $evolucionRepository): Response
    {
        $user = $this->getUser();
        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        $desde = $request->query->get('desde') ?? '';
        $hasta = $request->query->get('hasta') ?? '';
        $obraSocialSelected = $request->query->get('obraSocial') ?? '';
        $completados = $request->query->get('completados') ?? 1;
        $from = new \DateTime('2000-01-01');
        $to = new \DateTime();

        if($desde != '') {
            $from = (new \DateTime($desde));
        }
        if($hasta != '') {
            $to = (new \DateTime($hasta));
        }

        $doctor = $doctorRepository->find($id);
        $clientes = $clienteRepository->findByNombreYobraSocial(null, $obraSocialSelected);
        $bookings = $bookingRepository->turnosParaAgenda($doctor, $from, '', $clientes, $from, $to, $completados);

        $evoluciones = $evolucionRepository->findByFechaYDoctor($doctor->getEmail(), $from, $to);

        $obrasSociales = $obraSocialRepository->findAll();
        $obrasSocialesArray = [];

        foreach ($obrasSociales as $obrasSocial) {
            $obrasSocialesArray[$obrasSocial->getId()] = $obrasSocial->getNombre();
        }

        return $this->render('liquidaciones/liquidar.html.twig',
            [
                'bookings' => $bookings,
                'doctor' => $doctor,
                'desde' => $desde,
                'hasta' => $hasta,
                'obrasSociales' => $obrasSocialesArray,
                'obraSocialSelected' => $obraSocialSelected,
                'paginaImprimible' => true,
                'completados' => $completados,
                'evoluciones' => $evoluciones
            ]);
    }
}