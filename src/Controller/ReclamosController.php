<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class ReclamosController extends AbstractController
{
    /**
     * @Route("/reclamos", name="app_reclamos")
     */
    public function index(): Response
    {
        return $this->render('reclamos/index.html.twig', [
            'controller_name' => 'ReclamosController',
        ]);
    }
}
