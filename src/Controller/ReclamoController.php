<?php

namespace App\Controller;

use App\Entity\ComentarioReclamo;
use App\Entity\Reclamo;
use App\Form\ComentarioType;
use App\Form\ReclamoType;
use App\Repository\ComentarioReclamoRepository;
use App\Repository\ReclamoRepository;
use App\Service\EmailService;
use Doctrine\ORM\EntityManager;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Component\HttpFoundation\File\Exception\FileException;

/**
 * @Route("/reclamo")
 */
class ReclamoController extends AbstractController
{
    /**
     * @Route("/", name="app_reclamo_index", methods={"GET"})
     */
    public function index(Request $request, ReclamoRepository $reclamoRepository): Response
    {
        $user = $this->getUser();
        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        $currentPage = $request->query->get('currentPage') ?? 1;
        $tipo = $request->query->get('tipo') ?? null;
        $area = $request->query->get('area') ?? null;
        $estado = $request->query->get('estado') ?? null;
        $limit = $request->query->get('limit', 10);
        $desde = $request->query->get('desde', null);
        $hasta = $request->query->get('hasta', null);
        $orderBy = $request->query->get('orderBy', 'fecha');
        $maxPages = null;


        $reclamos = $reclamoRepository->findAllPaginados($tipo, $area, $estado, $currentPage, $limit, $desde, $hasta, $orderBy);
        $reclamos = $reclamos['paginator'];
        $maxPages = intval(ceil($reclamos->count() / $limit));

        return $this->render('reclamo/index.html.twig', [
            'reclamos' => $reclamos,
            'currentPage' => $currentPage,
            'tipo' => $tipo,
            'area' => $area,
            'estado' => $estado,
            'limit' => $limit,
            'desde' => $desde,
            'hasta' => $hasta,
            'orderBy' => $orderBy,
            'maxPages' => $maxPages,
        ]);
    }

    /**
     * @Route("/new", name="app_reclamo_new", methods={"GET", "POST"})
     */
    public function new(Request $request, ReclamoRepository $reclamoRepository, SluggerInterface $slugger, EmailService $emailService): Response
    {
        $user = $this->getUser();

        $reclamo = new Reclamo();
        $hoy = new \DateTime();
        $reclamo->setFecha($hoy);
        $reclamo->setFechaUltimaActualizacion($hoy);
        $reclamo->setTipo(2);
        $reclamo->setEstado(1);
        $area = $request->get('area', '');
        $reclamo->setArea($area);
        if ($user) {
            $reclamo->setTipo(1);
            $reclamo->setContacto($user->getEmail());
        }

        $form = $this->createForm(ReclamoType::class, $reclamo);
        $form->handleRequest($request);

        if ($form->isSubmitted()) {

            if ( !$form->isValid() ) {
                dd($form->getErrors());
            }

            $adjunto = $form->get('adjunto')->getData();
            
            if ( $adjunto ) {
                $originalFilename = pathinfo($adjunto->getClientOriginalName(), PATHINFO_FILENAME);
                $safeFilename = $slugger->slug($originalFilename);
                $newFilename = $safeFilename.'-'.uniqid().'.'.$adjunto->guessExtension();
                $path = $this->getParameter('adjuntos_reclamos_directory');
            
                try {
                    $adjunto->move(
                        $path,
                        $newFilename
                    );
                } catch (FileException $e) {
                    // ... handle exception if something happens during file upload
                    dd($e);
                }
                $path = $this->getParameter('adjuntos_reclamos_directory_url');
                $reclamo->setAdjunto($path."/".$newFilename);
            }

            $reclamoRepository->add($reclamo);


            /* 'Médica' => 1,
            'Enfermería' => 2,
            'General' => 3, */

            $recipients = ['pacientes.reclamos@gmail.com'];

            switch ($reclamo->getTipo()) {
                case 1:
                    $recipients[] = 'danielabraida77@hotmail.com';//area médica
                    break;
                case 2:
                    $recipients[] = 'avijarra@hotmail.com';//enfermería
                    break;
                case 3:
                    $recipients[] = 'marcelalucarelli2013@hotmail.com';//general
                    break;                    
            }
            
            
            $emailBody = $this->renderView('emails/reclamos/reclamo.html.twig', [
                'reclamo' => $reclamo,
            ]);

            $emailService->sendEmailToMultipleRecipients($recipients, 'Reclamo Generado', $emailBody);
            
            if (!$user) {
                return $this->redirectToRoute('app_reclamo_gracias', [], Response::HTTP_SEE_OTHER);
            } else {
                return $this->redirectToRoute('app_reclamo_index', [], Response::HTTP_SEE_OTHER);
            }
        }

        return $this->render('reclamo/new.html.twig', [
            'reclamo' => $reclamo,
            'form' => $form->createView(),
        ]);
    }
    /**
     * @Route("/gracias", name="app_reclamo_gracias", methods={"GET"})
     */
    public function gracias(): Response
    {
        return $this->render('reclamo/thanks.html.twig');
    }

    /**
     * @Route("/resuelto/{id}", name="app_reclamo_resolver", methods={"GET"})
     */
    public function resolver(Reclamo $reclamo, ReclamoRepository $reclamoRepository, EmailService $emailService): Response
    {
        $user = $this->getUser();
        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        $hoy = new \DateTime();
        $reclamo->setFecha($hoy);
        $reclamo->setFechaUltimaActualizacion($hoy);
        $reclamo->setEstado(3);
        $reclamoRepository->add($reclamo);

        // Lista de destinatarios
        $recipients = [$reclamo->getContacto()];
        $arrayWithLink = ['danielabraida77@hotmail.com', 'avijarra@hotmail.com', 'marcelalucarelli2013@hotmail.com', 'morimartin@gmail.com'];

        switch ($reclamo->getTipo()) {
            case 1:
                $recipients[] = 'danielabraida77@hotmail.com';//area médica
                break;
            case 2:
                $recipients[] = 'avijarra@hotmail.com';//enfermería
                break;
            case 3:
                $recipients[] = 'marcelalucarelli2013@hotmail.com';//general
                break;                    
        } 

        foreach ($recipients as $recipient) {
            $context = [
                'reclamo' => $reclamo,
                'showLink' => in_array($recipient, $arrayWithLink),
            ];

            // Renderiza el contenido del email usando Twig para cada destinatario
            $emailBody = $this->renderView('emails/reclamos/resuelto.html.twig', $context);

            // Envía el email a cada destinatario
            $emailService->sendEmail($recipient, 'Reclamo Resuelto', $emailBody);
        }

        return $this->redirectToRoute('app_reclamo_index', [], Response::HTTP_SEE_OTHER);
    }

    

    /**
    * @Route("/{id}", name="app_reclamo_show", methods={"GET","POST"})
    */
    public function show(Request $request, Reclamo $reclamo, ReclamoRepository $reclamoRepository, ComentarioReclamoRepository $comentarioReclamoRepository, EmailService $emailService): Response
    {
        // Cambiar el estado del reclamo
        $estado = $request->get('estado', 2);
        if ($reclamo->getEstado() == 3) {
            $estado = 3;
        }
        $reclamo->setEstado($estado);
        $reclamo->setFechaUltimaActualizacion(new \DateTime());
        $reclamoRepository->add($reclamo);

        // Lista de destinatarios
        $recipients = ['morimartin@gmail.com'];
        $arrayWithLink = ['danielabraida77@hotmail.com', 'avijarra@hotmail.com', 'marcelalucarelli2013@hotmail.com', 'morimartin@gmail.com'];

        /* switch ($reclamo->getTipo()) {
            case 1:
                $recipients[] = 'danielabraida77@hotmail.com';//area médica
                break;
            case 2:
                $recipients[] = 'avijarra@hotmail.com';//enfermería
                break;
            case 3:
                $recipients[] = 'marcelalucarelli2013@hotmail.com';//general
                break;                    
        } */

        foreach ($recipients as $recipient) {
            $context = [
                'reclamo' => $reclamo,
                'showLink' => in_array($recipient, $arrayWithLink),
            ];

            // Renderiza el contenido del email usando Twig para cada destinatario
            $emailBody = $this->renderView('emails/reclamos/visto.html.twig', $context);

            // Envía el email a cada destinatario
            $emailService->sendEmail($recipient, 'Reclamo en Proceso de resolución', $emailBody);
        }

        // Crear un nuevo comentario
        $comentario = new ComentarioReclamo();
        $comentario->setFecha(new \DateTime());

        // Crear el formulario
        $form = $this->createForm(ComentarioType::class, $comentario);
        $form->handleRequest($request);

        // Manejar el envío del formulario
        if ($form->isSubmitted() && $form->isValid()) {
            $comentario->setReclamo($reclamo); // Establecer la relación
            $comentarioReclamoRepository->add($comentario);

            return $this->redirectToRoute('app_reclamo_show', ['id' => $reclamo->getId(), 'estado' => 4]); // Redireccionar para evitar reenvío de formulario
        }

        return $this->render('reclamo/show.html.twig', [
            'reclamo' => $reclamo,
            'form' => $form->createView(), // Pasar el formulario a la vista
        ]);
    }

    

    /**
     * @Route("/{id}/edit", name="app_reclamo_edit", methods={"GET", "POST"})
     */
    public function edit(Request $request, Reclamo $reclamo, ReclamoRepository $reclamoRepository): Response
    {
        $form = $this->createForm(ReclamoType::class, $reclamo);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $reclamo->setFechaUltimaActualizacion(new \DateTime());
            $reclamoRepository->add($reclamo);
            return $this->redirectToRoute('app_reclamo_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('reclamo/edit.html.twig', [
            'reclamo' => $reclamo,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/{id}", name="app_reclamo_delete", methods={"POST"})
     */
    public function delete(Request $request, Reclamo $reclamo, ReclamoRepository $reclamoRepository): Response
    {
        if ($this->isCsrfTokenValid('delete'.$reclamo->getId(), $request->request->get('_token'))) {
            $reclamoRepository->remove($reclamo);
        }

        return $this->redirectToRoute('app_reclamo_index', [], Response::HTTP_SEE_OTHER);
    }
    
    /**
     * @Route("/{id}", name="app_reclamo_delete", methods={"POST"})
     */
    /* public function delete(Request $request, Reclamo $reclamo, ReclamoRepository $reclamoRepository): Response
    {
        if ($this->isCsrfTokenValid('delete'.$reclamo->getId(), $request->request->get('_token'))) {
            $reclamoRepository->remove($reclamo);
        }

        return Storage::download('file.jpg');
    } */
    

}
