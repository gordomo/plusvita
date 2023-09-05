<?php

namespace App\Controller;

use App\Entity\Doctor;
use App\Entity\Evolucion;
use App\Form\EvolucionType;
use App\Repository\ClienteRepository;
use App\Repository\EvolucionRepository;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use http\Client;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Component\Validator\Exception\UnexpectedValueException;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * @Route("/evolucion")
 */
class EvolucionController extends AbstractController
{
    /**
     * @Route("/", name="evolucion_index", methods={"GET"})
     */
    public function index(Request $request, EvolucionRepository $evolucionRepository, ClienteRepository $clienteRepository): Response
    {
        $user = $this->getUser();
        $tipoSeleccionado = $request->query->get('tipoSeleccionado', 0);

        $modalidades = [];
        if($user instanceOf Doctor) {
            $modalidades = $user->getModalidad();
        }

        if( count($modalidades) === 1 && $tipoSeleccionado === 0) {
            $tipoSeleccionado = $modalidades[0];
        }

        $cliente = $clienteRepository->find($request->get('cliente'));

        if($cliente->getModalidad() == 1 && !$cliente->getAmbulatorioPresente()) {
            die('ausente');
        }

        $evoluciones = $evolucionRepository->findByClienteYTipo($cliente, $tipoSeleccionado);

        return $this->render('evolucion/index.html.twig', [
            'nombreCliente' => $cliente->getNombre() . ' ' . $cliente->getApellido(),
            'evolucions' => $evoluciones,
            'clienteId' => $cliente->getId(),
            'tipoSeleccionado' => $tipoSeleccionado,
        ]);
    }

    /**
     * @Route("/new", name="evolucion_new", methods={"GET","POST"})
     */
    public function new(SluggerInterface $slugger, ValidatorInterface $validator, Request $request, ClienteRepository $clienteRepository, EvolucionRepository $evolucionRepository): Response
    {
        $user = $this->getUser();
        $error = '';
        if (!$user) {
            return $this->redirectToRoute('app_login');
        }
        $evolucion = new Evolucion();

        $cliente = $clienteRepository->find($request->get('cliente'));
        if($cliente->getDerivado() && $cliente->getId() != 920) die('paciente derivado, no se puede evolucionar');
        $evolucion->setPaciente($cliente);
        $evolucion->setUser($user->getEmail());
        $evolucion->setFecha(new \DateTime());

        $modalidades = $user->getModalidad();
        $modalidad = '';
        if( count($modalidades) === 1 ) {
            $modalidad = $modalidades[0];
        }

        $form = $this->createForm(EvolucionType::class, $evolucion, ['modalidad' => $modalidad]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

                $entityManager = $this->getDoctrine()->getManager();

                $adjuntos = $form->get('adjunto')->getData();
                foreach($adjuntos as $adjunto) {
                    $originalFilename = pathinfo($adjunto->getClientOriginalName(), PATHINFO_FILENAME);
                    $safeFilename = $slugger->slug($originalFilename);
                    $newFilename = $safeFilename.'-'.uniqid().'.'.$adjunto->guessExtension();

                    try {
                        $adjunto->move(
                            $this->getParameter('adjuntos_pacientes_directory') . '/' . $cliente->getId() . '/evoluciones/',
                            $newFilename
                        );
                    } catch (FileException $e) {
                        dd($e->getMessage());
                        // ... handle exception if something happens during file upload
                    }

                    $evolucion->addAdjuntoUrl($newFilename);
                }

                $entityManager->persist($evolucion);
                $entityManager->flush();

                return $this->redirectToRoute('evolucion_index', ['cliente' => $cliente->getId()], Response::HTTP_SEE_OTHER);

        } else {
            $errors = $validator->validate($form);
            if (!empty($errors[0])) {
                $error = $errors[0]->getMessage();
            }
        }

        return $this->render('evolucion/new.html.twig', [
            'evolucion' => $evolucion,
            'nombreCliente' => $cliente->getNombre() . ' ' . $cliente->getApellido(),
            'form' => $form->createView(),
            'clienteId' => $cliente->getId(),
            'error' => $error,
        ]);



    }

    /**
     * @Route("/{id}", name="evolucion_show", methods={"GET"})
     */
    public function show($id, EvolucionRepository $evolucionRepository): Response
    {
        $evolucion = $evolucionRepository->find($id);
        return $this->render('evolucion/show.html.twig', [
            'evolucion' => $evolucion,
        ]);
    }

    /**
     * @Route("/{id}/edit", name="evolucion_edit", methods={"GET","POST"})
     */
    public function edit(Request $request, Evolucion $evolucion): Response
    {
        $userName = $this->getUser()->getUsername();
        $redirect = $request->get('redirect', '');

        $puedenEditarEvoluciones = in_array('ROLE_EDIT_HC', $this->getUser()->getRoles());

        if ( $puedenEditarEvoluciones ) {
            $form = $this->createForm(EvolucionType::class, $evolucion);
            $form->handleRequest($request);

            if ($form->isSubmitted() && $form->isValid()) {
                $this->getDoctrine()->getManager()->flush();

                if($redirect !== '') {
                    return $this->redirect($redirect);
                } else {
                    return $this->redirectToRoute('cliente_historial', ['id' => $evolucion->getPaciente()->getId()], Response::HTTP_SEE_OTHER);
                }
            }

            return $this->render('evolucion/edit.html.twig', [
                'evolucion' => $evolucion,
                'clienteId' => $evolucion->getPaciente()->getId(),
                'form' => $form->createView(),
            ]);
        } else {
            return $this->redirectToRoute('cliente_historial', ['id' => $evolucion->getPaciente()->getId()], Response::HTTP_SEE_OTHER);
        }

    }

    /**
     * @Route("/{id}", name="evolucion_delete", methods={"POST"})
     */
    public function delete(Request $request, Evolucion $evolucion): Response
    {
        $clienteId = $evolucion->getPaciente()->getId();
        $redirect = $request->get('redirect', '');

        $puedenEditarEvoluciones = in_array('ROLE_EDIT_HC', $this->getUser()->getRoles());

        if( $puedenEditarEvoluciones ) {
            if ($this->isCsrfTokenValid('delete'.$evolucion->getId(), $request->request->get('_token'))) {
                $entityManager = $this->getDoctrine()->getManager();
                $entityManager->remove($evolucion);
                $entityManager->flush();
            }
        }
        if($redirect !== '') {
            return $this->redirect($redirect);
        } else {
            return $this->redirectToRoute('evolucion_index', ['cliente' => $clienteId], Response::HTTP_SEE_OTHER);
        }
    }

    /**
     * @Route("/get-adjunto/{pdf}/{clienteId}", name="evolucion_get_adjunto", methods={"GET"})
     */
    public function getFile(Request $request, $clienteId, $pdf): Response
    {
        $file = $this->getParameter('adjuntos_pacientes_directory') . '/' . $clienteId . '/evoluciones/' . $pdf;
        return new BinaryFileResponse($file);
    }
}
