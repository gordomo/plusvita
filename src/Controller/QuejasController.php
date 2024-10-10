<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class QuejasController extends AbstractController
{
    /**
     * @Route("/quejas", name="app_quejas")
     */
    public function index(): Response
    {
        return $this->render('quejas/index.html.twig', [
            'controller_name' => 'QuejasController',
        ]);
    }
}
