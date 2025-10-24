<?php

namespace App\Controller;

use App\Entity\UserPresente;
use App\Repository\UserRepository;
use App\Repository\UserPresenteRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/presentes")
 */
class UserPresenteController extends AbstractController
{
    /**
     * @Route("/doctores", name="presentes_doctores", methods={"GET"})
     */
    public function index(UserRepository $userRepository, UserPresenteRepository $presRepo): Response
    {
        // Listar usuarios con rol 'doctor'
        $doctores = $userRepository->findByRole('doctor');

        // Mapear estado de presente para hoy
        $hoy = new \DateTime('today');
        $estados = [];
        foreach ($doctores as $doc) {
            $estados[$doc->getId()] = $presRepo->hasPresente($doc, $hoy);
        }

        return $this->render('presentes/doctores.html.twig', [
            'doctores' => $doctores,
            'estados' => $estados,
            'hoy' => $hoy->format('Y-m-d'),
        ]);
    }

    /**
     * @Route("/toggle/{id}", name="presentes_toggle", methods={"POST"})
     */
    public function toggle(int $id, Request $request, UserRepository $userRepository, UserPresenteRepository $presRepo, EntityManagerInterface $em): Response
    {
        $this->denyAccessUnlessGranted('patient.attendance'); // reutilizamos permiso de asistencia

        $token = $request->request->get('_token');
        if (!$this->isCsrfTokenValid('toggle_presente_' . $id, $token)) {
            throw $this->createAccessDeniedException('Token CSRF inválido');
        }

        $user = $userRepository->find($id);
        if (!$user) {
            throw $this->createNotFoundException('Usuario no encontrado');
        }

        $hoy = new \DateTime('today');

    // Buscar registro existente
        $existing = $presRepo->findOneBy(['user' => $user, 'fecha' => $hoy]);
        if ($existing) {
            // Toggle
            $existing->setValor(!$existing->getValor());
            $em->persist($existing);
        } else {
            $pres = new UserPresente();
            $pres->setUser($user)->setFecha($hoy)->setValor(true);
            $em->persist($pres);
        }
        $em->flush();

        return $this->redirectToRoute('presentes_doctores');
    }
}
