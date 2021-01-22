<?php

namespace App\Controller;

use App\Repository\BookingRepository;
use App\Repository\DoctorRepository;
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
        $contratosParaBusqueda = array_merge($directo, $prestacion, $sinContrato);
        $contratosParaVista = ['directo' => $directo, 'prestacion' => $prestacion, 'sinContrato' => $sinContrato];

        $ctrs = $request->query->get('ctr');
        $ctrsArray = explode(',', $ctrs);


        if(!empty($ctrs)) {
            $profesionales = $doctorRepository->findByContratos($ctrsArray);
        } else {
            $profesionales = $doctorRepository->findByContratos($contratosParaBusqueda);
        }


        return $this->render('liquidaciones/profesionales.html.twig', [
            'doctors' => $profesionales,
            'contratos' => $contratosParaVista,
            'ctrsArray' => $ctrsArray
        ]);
    }
    /**
     * @Route("/profesional/{id}", name="liquidar", methods={"GET"})
     */
    public function liquidar($id, DoctorRepository $doctorRepository, BookingRepository $bookingRepository): Response
    {
        $doctor = $doctorRepository->find($id);
        $bookings = $bookingRepository->findBy(['doctor' => $doctor]);

        return $this->render('liquidaciones/liquidar.html.twig',
            [
                'bookings' => $bookings,
                'doctor' => $doctor,
            ]);
    }
}