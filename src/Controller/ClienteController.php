<?php

namespace App\Controller;

use App\Entity\Cliente;
use App\Entity\Doctor;
use App\Form\ClienteType;
use App\Repository\ClienteRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/cliente")
 */
class ClienteController extends AbstractController
{
    /**
     * @Route("/", name="cliente_index", methods={"GET"})
     */
    public function index(ClienteRepository $clienteRepository): Response
    {
        return $this->render('cliente/index.html.twig', [
            'clientes' => $clienteRepository->findAll(),
        ]);
    }

    /**
     * @Route("/patologia-select", name="paciente_patologia_select")
     */
    public function getPatologiasSelect(Request $request): Response
    {
        $cliente = new Cliente();
        $cliente->setMotivoIng($request->query->get('motivoIng'));
        $form = $this->createForm(ClienteType::class, $cliente);

        if (!$form->has('motivoIngEspecifico')) {
            return new Response(null, 204);
        }

        return $this->render('cliente/_motivoIngEspecifico.html.twig', [
            'form' => $form->createView(),
        ]);

    }

    /**
     * @Route("/new", name="cliente_new", methods={"GET","POST"})
     */
    public function new(Request $request): Response
    {
        $cliente = new Cliente();
        $cliente->setActivo(true);
        $cliente->setFIngreso(new \DateTime());
        $form = $this->createForm(ClienteType::class, $cliente);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->persist($cliente);
            $entityManager->flush();

            return $this->redirectToRoute('cliente_index');
        }

        return $this->render('cliente/new.html.twig', [
            'cliente' => $cliente,
            'form' => $form->createView(),

        ]);
    }

    /**
     * @Route("/{id}", name="cliente_show", methods={"GET"})
     */
    public function show(Cliente $cliente): Response
    {
        return $this->render('cliente/show.html.twig', [
            'cliente' => $cliente,

        ]);
    }

    /**
     * @Route("/{id}/edit", name="cliente_edit", methods={"GET","POST"})
     */
    public function edit(Request $request, Cliente $cliente): Response
    {
        $form = $this->createForm(ClienteType::class, $cliente);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->getDoctrine()->getManager()->flush();

            return $this->redirectToRoute('cliente_index');
        }

        return $this->render('cliente/edit.html.twig', [
            'cliente' => $cliente,
            'form' => $form->createView(),

        ]);
    }

    /**
     * @Route("/{id}", name="cliente_delete", methods={"DELETE"})
     */
    public function delete(Request $request, Cliente $cliente): Response
    {
        if ($this->isCsrfTokenValid('delete'.$cliente->getId(), $request->request->get('_token'))) {
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->remove($cliente);
            $entityManager->flush();
        }

        return $this->redirectToRoute('cliente_index');
    }
}
