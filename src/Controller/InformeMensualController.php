<?php

namespace App\Controller;

use App\Entity\Cliente;
use App\Entity\Doctor;
use App\Entity\InformeMensual;
use App\Form\InformeMensualType;
use App\Repository\ClienteRepository;
use App\Repository\InformeMensualRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Dompdf\Dompdf;
use Dompdf\Options;

/**
 * @Route("/informe/mensual")
 */
class InformeMensualController extends AbstractController
{
    /**
     * @Route("/", name="informe_mensual_index", methods={"GET"})
     */
    public function index(Request $request, InformeMensualRepository $informeMensualRepository, ClienteRepository $clienteRepository): Response
    {
        // Validar permisos
        if (!$this->isGranted('informe_mensual.view') && !$this->isGranted('informe_mensual.manage')) {
            throw $this->createAccessDeniedException('No tienes permiso para ver informes mensuales.');
        }

        // Obtener parámetros de filtrado de la URL
        $pacienteId = $request->query->get('paciente');
        $doctorId = $request->query->get('doctor');
        
        // Establecer valores por defecto para fechas (primer y último día del mes)
        $hoy = new \DateTime();
        $primerDiaDelMes = new \DateTime($hoy->format('Y-m-01'));
        $ultimoDiaDelMes = new \DateTime($hoy->format('Y-m-t'));
        $ultimoDiaDelMes->setTime(23, 59, 59);
        
        $desde = $request->query->get('desde', $primerDiaDelMes->format('Y-m-d'));
        $hasta = $request->query->get('hasta', $ultimoDiaDelMes->format('Y-m-d'));
        
        // Convertir cadenas de fecha a objetos DateTime
        $fechaDesde = null;
        $fechaHasta = null;
        
        try {
            if ($desde) {
                $fechaDesde = new \DateTime($desde);
            }
            
            if ($hasta) {
                $fechaHasta = new \DateTime($hasta);
                // Asegurar que la fecha hasta incluya todo el día
                $fechaHasta->setTime(23, 59, 59);
            }
        } catch (\Exception $e) {
            // Si hay un error de formato de fecha, ignorar ese filtro
            $this->addFlash('error', 'Formato de fecha incorrecto. Por favor use YYYY-MM-DD');
        }
        
        // Obtener los informes filtrados
        $canManage = $this->isGranted('informe_mensual.manage');
        $userEmail = $this->getUser()->getEmail();
        $informes = $informeMensualRepository->findByFilters(
            $pacienteId, 
            $doctorId, 
            $fechaDesde, 
            $fechaHasta,
            $userEmail,
            $canManage
        );
        
        // Obtener la lista de pacientes para el filtro
        $pacientes = $clienteRepository->findBy([], ['apellido' => 'ASC']);
        
        // Obtener la lista de doctores para el filtro
        $entityManager = $this->getDoctrine()->getManager();
        $doctores = $entityManager->getRepository(Doctor::class)->findBy([], ['apellido' => 'ASC']);

        return $this->render('informe_mensual/index.html.twig', [
            'informes' => $informes,
            'pacientes' => $pacientes,
            'doctores' => $doctores,
            'filtros' => [
                'pacienteId' => $pacienteId,
                'doctorId' => $doctorId,
                'desde' => $desde,
                'hasta' => $hasta,
            ]
        ]);
    }

    /**
     * @Route("/new/{cliente}", name="informe_mensual_new", methods={"GET", "POST"})
     */
    public function new(Request $request, Cliente $cliente, InformeMensualRepository $informeMensualRepository): Response
    {
        // Validar permisos: Necesita view (para crear los suyos) o manage (para crear de otros)
        if (!$this->isGranted('informe_mensual.view') && !$this->isGranted('informe_mensual.manage')) {
            throw $this->createAccessDeniedException('No tienes permiso para crear informes mensuales.');
        }
        
        $user = $this->getUser();
        
        if (!$user) {
            return $this->redirectToRoute('app_login');
        }
        
        $informeMensual = new InformeMensual();
        $informeMensual->setCliente($cliente);
        $informeMensual->setDoctor($user);  // Nuevo sistema: User
        
        // Generar información automática del estado actual del paciente
        $estadoActualSugerido = $this->generarEstadoActualPaciente($cliente);
        $informeMensual->setEstadoActual($estadoActualSugerido);
        
        $form = $this->createForm(InformeMensualType::class, $informeMensual);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $informeMensualRepository->save($informeMensual, true);

            $this->addFlash('success', 'Informe mensual creado correctamente.');
            return $this->redirectToRoute('informe_mensual_show', ['id' => $informeMensual->getId()], Response::HTTP_SEE_OTHER);
        }

        return $this->render('informe_mensual/new.html.twig', [
            'informe_mensual' => $informeMensual,
            'doctor' => $user,
            'cliente' => $cliente,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/{id}", name="informe_mensual_show", methods={"GET"})
     */
    public function show(InformeMensual $informeMensual): Response
    {
        // Validar permisos
        if (!$this->isGranted('informe_mensual.view') && !$this->isGranted('informe_mensual.manage')) {
            throw $this->createAccessDeniedException('No tienes permiso para ver informes mensuales.');
        }
        
        // Si solo tiene view, verificar que sea su propio informe
        if (!$this->isGranted('informe_mensual.manage')) {
            if (!$informeMensual->getDoctor() || $informeMensual->getDoctor()->getEmail() !== $this->getUser()->getEmail()) {
                throw $this->createAccessDeniedException('Solo puedes ver tus propios informes.');
            }
        }
        
        return $this->render('informe_mensual/show.html.twig', [
            'informeMensual' => $informeMensual,
        ]);
    }

    /**
     * @Route("/{id}/edit", name="informe_mensual_edit", methods={"GET", "POST"})
     */
    public function edit(Request $request, InformeMensual $informeMensual, InformeMensualRepository $informeMensualRepository): Response
    {
        // Validar permisos: Necesita view (para editar los suyos) o manage (para editar de otros)
        if (!$this->isGranted('informe_mensual.view') && !$this->isGranted('informe_mensual.manage')) {
            throw $this->createAccessDeniedException('No tienes permiso para editar informes mensuales.');
        }
        
        // Si solo tiene view, verificar que sea su propio informe
        if (!$this->isGranted('informe_mensual.manage')) {
            if (!$informeMensual->getDoctor() || $informeMensual->getDoctor()->getEmail() !== $this->getUser()->getEmail()) {
                throw $this->createAccessDeniedException('Solo puedes editar tus propios informes.');
            }
        }

        $form = $this->createForm(InformeMensualType::class, $informeMensual);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $informeMensualRepository->save($informeMensual, true);

            $this->addFlash('success', 'Informe mensual actualizado correctamente.');
            return $this->redirectToRoute('informe_mensual_show', ['id' => $informeMensual->getId()], Response::HTTP_SEE_OTHER);
        }

        return $this->render('informe_mensual/edit.html.twig', [
            'informeMensual' => $informeMensual,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/{id}/delete", name="informe_mensual_delete", methods={"GET", "POST"})
     */
    public function delete(Request $request, InformeMensual $informeMensual, InformeMensualRepository $informeMensualRepository): Response
    {
        // Validar permisos: Solo manage puede eliminar
        if (!$this->isGranted('informe_mensual.manage')) {
            throw $this->createAccessDeniedException('No tienes permiso para eliminar informes mensuales.');
        }
        
        // Eliminamos el informe mensual directamente
        $informeMensualRepository->remove($informeMensual, true);
        $this->addFlash('success', 'Informe mensual eliminado correctamente.');

        return $this->redirectToRoute('doctor_historia', [], Response::HTTP_SEE_OTHER);
    }

    /**
     * @Route("/{id}/pdf", name="informe_mensual_pdf", methods={"GET"})
     */
    public function generarPdf(InformeMensual $informeMensual): Response
    {
        // Validar permisos
        if (!$this->isGranted('informe_mensual.view') && !$this->isGranted('informe_mensual.manage')) {
            throw $this->createAccessDeniedException('No tienes permiso para ver este informe.');
        }
        
        // Si solo tiene view, verificar que sea su propio informe
        if (!$this->isGranted('informe_mensual.manage')) {
            if (!$informeMensual->getDoctor() || $informeMensual->getDoctor()->getEmail() !== $this->getUser()->getEmail()) {
                throw $this->createAccessDeniedException('Solo puedes generar PDF de tus propios informes.');
            }
        }

        // Obtener la fecha personalizada del request, o usar la fecha de creación por defecto
        $fechaPersonalizada = $this->get('request_stack')->getCurrentRequest()->query->get('fecha');
        
        if ($fechaPersonalizada) {
            try {
                $fecha = new \DateTime($fechaPersonalizada);
            } catch (\Exception $e) {
                $fecha = $informeMensual->getFechaCreacion();
            }
        } else {
            $fecha = $informeMensual->getFechaCreacion();
        }
        
        // Obtener datos de la firma del doctor
        $doctorSignature = $this->obtenerFirmaDelInforme($informeMensual);
        
        // Configurar Dompdf
        $options = new Options();
        $options->set('isHtml5ParserEnabled', true);
        $options->set('isRemoteEnabled', true);
        $options->set('isPhpEnabled', true);
        $options->set('isJavascriptEnabled', true);
        
        // Configurar el directorio raíz para las imágenes
        $publicDir = $this->getParameter('kernel.project_dir') . '/public';
        $options->set('chroot', $publicDir);
        
        $dompdf = new Dompdf($options);
        
        // Obtener el host para las URLs
        $request = $this->container->get('request_stack')->getCurrentRequest();
        $baseUrl = $request ? $request->getSchemeAndHttpHost() : '';
        
        // Renderizar la vista que queremos convertir a PDF
        $html = $this->renderView('informe_mensual/pdf.html.twig', [
            'informeMensual' => $informeMensual,
            'baseUrl' => $baseUrl,
            'fechaPersonalizada' => $fecha,
            'doctorSignature' => $doctorSignature
        ]);
        
        // Cargar HTML en Dompdf
        $dompdf->loadHtml($html);
        
        // Establecer el tamaño del papel y orientación
        $dompdf->setPaper('A4', 'portrait');
        
        // Renderizar el PDF
        $dompdf->render();
        
        // Generar nombre de archivo
        $cliente = $informeMensual->getCliente();
        $fechaFormateada = $fecha->format('Y-m-d');
        $filename = 'informe_mensual_' . $cliente->getApellido() . '_' . $fechaFormateada . '.pdf';
        
        // Descargar el PDF generado
        return new Response(
            $dompdf->output(),
            Response::HTTP_OK,
            [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'attachment; filename="' . $filename . '"'
            ]
        );
    }

    /**
     * @Route("/{id}/pdf-form", name="informe_mensual_pdf_form", methods={"GET"})
     */
    public function mostrarFormularioPdf(InformeMensual $informeMensual): Response
    {
        // Validar permisos (mismo que show y generarPdf)
        if (!$this->isGranted('informe_mensual.view') && !$this->isGranted('informe_mensual.manage')) {
            throw $this->createAccessDeniedException('No tienes permiso para ver este informe.');
        }
        
        // Si solo tiene view, verificar que sea su propio informe
        if (!$this->isGranted('informe_mensual.manage')) {
            if (!$informeMensual->getDoctor() || $informeMensual->getDoctor()->getEmail() !== $this->getUser()->getEmail()) {
                throw $this->createAccessDeniedException('Solo puedes generar PDF de tus propios informes.');
            }
        }
        
        return $this->render('informe_mensual/pdf_form.html.twig', [
            'informeMensual' => $informeMensual,
        ]);
    }
    
    /**
     * Genera automáticamente la información del estado actual del paciente
     */
    private function generarEstadoActualPaciente(Cliente $cliente): string
    {
        $estadoActual = [];
        
        // 1. Estado de modalidad del paciente
        $modalidad = $cliente->getModalidad();
        switch ($modalidad) {
            case 1:
                $estadoActual[] = "• Modalidad: Ambulatorio";
                break;
            case 2:
                $estadoActual[] = "• Modalidad: Internación";
                if ($cliente->getHabitacion()) {
                    $entityManager = $this->getDoctrine()->getManager();
                    $habitacion = $entityManager->getRepository(\App\Entity\Habitacion::class)->find($cliente->getHabitacion());
                    if ($habitacion) {
                        $estadoActual[] = "  - Habitación: " . $habitacion->getNombre() . ", Cama: " . ($cliente->getNCama() ?: 'No asignada');
                    }
                }
                break;
            case 3:
                $estadoActual[] = "• Modalidad: Hospital de día";
                break;
            case 4:
                $estadoActual[] = "• Modalidad: ART";
                break;
            default:
                $estadoActual[] = "• Modalidad: No definida";
        }
        
        // 2. Estados especiales
        if ($cliente->getDerivado()) {
            $estadoActual[] = "• Estado: Derivado";
            if ($cliente->getDerivadoEn()) {
                $estadoActual[] = "  - Derivado en: " . $cliente->getDerivadoEn();
            }
            if ($cliente->getFechaDerivacion()) {
                $estadoActual[] = "  - Fecha de derivación: " . $cliente->getFechaDerivacion()->format('d/m/Y');
            }
            if ($cliente->getMotivoDerivacion()) {
                $estadoActual[] = "  - Motivo: " . $cliente->getMotivoDerivacion();
            }
        }
        
        if ($cliente->getDePermiso()) {
            $estadoActual[] = "• Estado: De permiso";
            if ($cliente->getFechaBajaPorPermiso()) {
                $estadoActual[] = "  - Desde: " . $cliente->getFechaBajaPorPermiso()->format('d/m/Y');
            }
            if ($cliente->getFechaAltaPorPermiso()) {
                $estadoActual[] = "  - Hasta: " . $cliente->getFechaAltaPorPermiso()->format('d/m/Y');
            }
        }
        
        if ($cliente->getFEgreso()) {
            $estadoActual[] = "• Estado: Egresado";
            $estadoActual[] = "  - Fecha de egreso: " . $cliente->getFEgreso()->format('d/m/Y');
            if ($cliente->getMotivoEgr()) {
                $estadoActual[] = "  - Motivo: " . $cliente->getMotivoEgr();
            }
        }
        
        // 3. Disponibilidad para terapia
        if ($cliente->getDisponibleParaTerapia() !== null) {
            $disponible = $cliente->getDisponibleParaTerapia() ? "Sí" : "No";
            $estadoActual[] = "• Disponible para terapia: " . $disponible;
        }
        
        // 4. Obra social
        if ($cliente->getObraSocial()) {
            $estadoActual[] = "• Obra Social: " . $cliente->getObraSocial()->getNombre();
            if ($cliente->getObraSocialAfiliado()) {
                $estadoActual[] = "  - N° Afiliado: " . $cliente->getObraSocialAfiliado();
            }
        }
        
        // 5. Médico referente
        if ($cliente->getDocReferente() && count($cliente->getDocReferente()) > 0) {
            $doctores = [];
            foreach ($cliente->getDocReferente() as $doctor) {
                $doctores[] = $doctor->getNombre() . ' ' . $doctor->getApellido();
            }
            $estadoActual[] = "• Médico/s referente/s: " . implode(', ', $doctores);
        }
        
        // 6. Fecha de ingreso
        if ($cliente->getFIngreso()) {
            $estadoActual[] = "• Fecha de ingreso: " . $cliente->getFIngreso()->format('d/m/Y');
        }
        
        // 7. Patología actual
        if ($cliente->getMotivoIng()) {
            $patologias = [
                1 => 'Neurológicas',
                2 => 'Traumatológicas', 
                3 => 'Respiratorias',
                4 => 'Paliativos',
                5 => 'Patologías laborales'
            ];
            if (isset($patologias[$cliente->getMotivoIng()])) {
                $estadoActual[] = "• Patología: " . $patologias[$cliente->getMotivoIng()];
            }
        }
        
        // 8. Agregar fecha de generación
        $estadoActual[] = "";
        $estadoActual[] = "Estado generado automáticamente el " . (new \DateTime())->format('d/m/Y H:i');
        
        return implode("\n", $estadoActual);
    }
    
    /**
     * Obtiene la firma del doctor (UserFirma o Doctor.firma)
     */
    private function obtenerFirmaDoctor(Doctor $doctor): array
    {
        $firma = '';
        $doctorData = [
            'nombre' => $doctor->getNombre(),
            'apellido' => $doctor->getApellido(),
            'matricula' => $doctor->getMatricula(),
            'firma_path' => null,
        ];
        
        $entityManager = $this->getDoctrine()->getManager();
        
        // 1. Buscar por User con firmas activas
        $userRepository = $entityManager->getRepository(\App\Entity\User::class);
        $user = $userRepository->findOneBy(['email' => $doctor->getEmail()]);
        
        if ($user) {
            // Actualizar datos del usuario si están disponibles
            $doctorData['nombre'] = $user->getNombre() ?: $doctor->getNombre();
            $doctorData['apellido'] = $user->getApellido() ?: $doctor->getApellido();
            $doctorData['matricula'] = $user->getLegajo() ?: $doctor->getMatricula();
            
            // Buscar firma activa en las firmas del usuario
            $firmas = $user->getFirmas();
            if ($firmas && count($firmas) > 0) {
                foreach ($firmas as $firmaItem) {
                    if ($firmaItem->getIsActive() && $firmaItem->getFilePath()) {
                        $firma = $firmaItem->getFilePath();
                        break;
                    }
                }
            }
        }
        
        // 2. Si no encontró firma en User, usar firma del Doctor entity
        if (empty($firma) && $doctor->getFirma()) {
            $firma = $doctor->getFirma();
        }
        
        $doctorData['firma_path'] = $firma;
        
        return $doctorData;
    }
    
    /**
     * Obtiene la firma del InformeMensual (User con fallback a firma vieja en Doctor)
     */
    private function obtenerFirmaDelInforme(InformeMensual $informe): array
    {
        $usuarioDoctor = $informe->getDoctor();
        $firma = '';
        $doctorData = [
            'nombre' => '',
            'apellido' => '',
            'matricula' => null,
            'firma_path' => null,
        ];
        
        // Si tiene User asociado
        if ($usuarioDoctor) {
            // Obtener datos del usuario
            $doctorData['nombre'] = $usuarioDoctor->getNombre() ?: '';
            $doctorData['apellido'] = $usuarioDoctor->getApellido() ?: '';
            $doctorData['matricula'] = $usuarioDoctor->getLegajo() ?: null;
            
            // Buscar firma activa en las firmas del usuario
            $firmas = $usuarioDoctor->getFirmas();
            if ($firmas && count($firmas) > 0) {
                foreach ($firmas as $firmaItem) {
                    if ($firmaItem->getIsActive() && $firmaItem->getFilePath()) {
                        $firma = $firmaItem->getFilePath();
                        break; // Solo necesitamos la primera firma activa
                    }
                }
            }
            
            // Si no encontró firma en User, buscar en Doctor (sistema viejo)
            if (empty($firma)) {
                $entityManager = $this->getDoctrine()->getManager();
                $doctorRepository = $entityManager->getRepository(Doctor::class);
                $doctorLegado = $doctorRepository->findOneBy(['email' => $usuarioDoctor->getEmail()]);
                
                if ($doctorLegado) {
                    $firmaDoctor = $doctorLegado->getFirma();
                    if ($firmaDoctor) {
                        $firma = $firmaDoctor;
                    }
                    
                    // Complementar datos si faltan
                    if (empty($doctorData['nombre'])) {
                        $doctorData['nombre'] = $doctorLegado->getNombre() ?: '';
                    }
                    if (empty($doctorData['apellido'])) {
                        $doctorData['apellido'] = $doctorLegado->getApellido() ?: '';
                    }
                    if (empty($doctorData['matricula'])) {
                        $doctorData['matricula'] = $doctorLegado->getMatricula() ?: null;
                    }
                }
            }
            
            $doctorData['firma_path'] = $firma;
            return $doctorData;
        }
        
        // Si no hay usuario asociado
        return [
            'nombre' => 'Desconocido',
            'apellido' => '',
            'matricula' => null,
            'firma_path' => null,
        ];
    }
}
