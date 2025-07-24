<?php

namespace App\Controller;

use App\Entity\Cliente;
use App\Repository\ConsumiblesClientesRepository;
use App\Repository\HistoriaIngresoRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/cardex")
 */
class CardexController extends AbstractController
{
    /**
     * @Route("/paciente/{id}", name="cardex_paciente", methods={"GET"})
     */
    public function index(Request $request, Cliente $cliente, ConsumiblesClientesRepository $consumiblesClientesRepository, HistoriaIngresoRepository $historiaIngresoRepository): Response
    {
        // Obtener indicaciones médicas para este paciente (incluye tanto activas como inactivas)
        $indicaciones = $consumiblesClientesRepository->findIndicacionesParaElCliente($cliente->getId(), null, null, null, false);
        
        // Agrupar indicaciones por mes y año para el filtro
        $years = [];
        $months = [];
        $categories = [];
        
        foreach ($indicaciones as $indicacion) {
            // Añadir el año si no existe en el array
            if (!in_array($indicacion['year'], $years)) {
                $years[] = $indicacion['year'];
            }
            
            // Añadir el mes si no existe en el array
            if (!in_array($indicacion['mes'], $months)) {
                $months[] = $indicacion['mes'];
            }
            
            // Añadir la categoría si existe y no está en el array
            if (isset($indicacion['tipo']) && !in_array($indicacion['tipo'], $categories)) {
                $categories[] = $indicacion['tipo'];
            }
        }
        
        // Ordenar años y meses
        sort($years);
        sort($months);
        sort($categories);
        
        // Obtener historial de ingreso para mostrar indicaciones iniciales
        $historiaIngreso = $historiaIngresoRepository->findOneBy(['cliente' => $cliente]);
        
        // Definir nombres de meses para mostrar en la plantilla
        $mesesNombres = [
            '01' => 'Enero',
            '02' => 'Febrero',
            '03' => 'Marzo',
            '04' => 'Abril',
            '05' => 'Mayo',
            '06' => 'Junio',
            '07' => 'Julio',
            '08' => 'Agosto',
            '09' => 'Septiembre',
            '10' => 'Octubre',
            '11' => 'Noviembre',
            '12' => 'Diciembre',
        ];
        
        // Verificar si el usuario es un doctor
        $user = $this->getUser();
        $isDoctor = false;
        if ($user && $this->isGranted('ROLE_DOCTOR')) {
            $isDoctor = true;
        } elseif ($user && $this->isGranted('ROLE_STAFF') && $user->getDoctor() !== null) {
            $isDoctor = true;
        }
        
        return $this->render('cardex/index.html.twig', [
            'cliente' => $cliente,
            'indicaciones' => $indicaciones,
            'historiaIngreso' => $historiaIngreso,
            'years' => $years,
            'months' => $months,
            'categories' => $categories,
            'mesesNombres' => $mesesNombres,
            'currentYear' => date('Y'),
            'currentMonth' => date('m'),
            'isDoctor' => $isDoctor,
        ]);
    }
}
