<?php

namespace App\Controller;

use App\Entity\Nurse;
use App\Form\NurseType;
use App\Repository\NurseRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;

/**
 * @Route("/nurse")
 */
class NurseController extends AbstractController
{
    private $passwordEncoder;

    public function __construct(UserPasswordEncoderInterface $passwordEncoder)
    {
        $this->passwordEncoder = $passwordEncoder;
    }

    /**
     * @Route("/", name="nurse_index", methods={"GET"})
     */
    public function index(NurseRepository $nurseRepository): Response
    {
        if (!$this->isGranted('nurse.read') && !$this->isGranted('ROLE_ADMIN')) {
            throw $this->createAccessDeniedException('No tienes permisos para ver enfermeros');
        }
        
        return $this->render('nurse/index.html.twig', [
            'nurses' => $nurseRepository->findAll(),
        ]);
    }

    /**
     * @Route("/new", name="nurse_new", methods={"GET","POST"})
     */
    public function new(Request $request): Response
    {
        if (!$this->isGranted('nurse.create') && !$this->isGranted('ROLE_ADMIN')) {
            throw $this->createAccessDeniedException('No tienes permisos para crear enfermeros');
        }
        
        $nurse = new Nurse();
        $nurse->setHabilitado(true);
        
        $form = $this->createForm(NurseType::class, $nurse);
        
        // Populate the unmapped roleEntities field with current nurse roles
        $form->get('roleEntities')->setData($nurse->getRoleEntities());

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Encriptar contraseña
            $nurse->setPassword($this->passwordEncoder->encodePassword($nurse, $nurse->getPassword()));
            
            // Handle the unmapped roleEntities field
            $selectedRoles = $form->get('roleEntities')->getData();
            $nurse->getRoleEntities()->clear();
            foreach ($selectedRoles as $role) {
                $nurse->addRole($role);
            }
            
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->persist($nurse);
            $entityManager->flush();

            return $this->redirectToRoute('nurse_index');
        }

        return $this->render('nurse/new.html.twig', [
            'nurse' => $nurse,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/{id}", name="nurse_show", methods={"GET"})
     */
    public function show(Nurse $nurse): Response
    {
        return $this->render('nurse/show.html.twig', [
            'nurse' => $nurse,
        ]);
    }

    /**
     * @Route("/{id}/edit", name="nurse_edit", methods={"GET","POST"})
     */
    public function edit(Request $request, Nurse $nurse): Response
    {
        $form = $this->createForm(NurseType::class, $nurse);
        
        // Populate the unmapped roleEntities field with current nurse roles
        $form->get('roleEntities')->setData($nurse->getRoleEntities());
        
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Handle the unmapped roleEntities field
            $selectedRoles = $form->get('roleEntities')->getData();
            $nurse->getRoleEntities()->clear();
            foreach ($selectedRoles as $role) {
                $nurse->addRole($role);
            }
            
            $this->getDoctrine()->getManager()->flush();

            return $this->redirectToRoute('nurse_index');
        }

        return $this->render('nurse/edit.html.twig', [
            'nurse' => $nurse,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/{id}", name="nurse_delete", methods={"DELETE"})
     */
    public function delete(Request $request, Nurse $nurse): Response
    {
        if ($this->isCsrfTokenValid('delete'.$nurse->getId(), $request->request->get('_token'))) {
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->remove($nurse);
            $entityManager->flush();
        }

        return $this->redirectToRoute('nurse_index');
    }
}
