<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Endroid\QrCodeBundle\Response\QrCodeResponse;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

use Endroid\QrCode\Builder\BuilderInterface;



class QrConstrollerController extends AbstractController
{
    /**
     * @Route("/qr/{id}", name="qr_index")
     */
    public function index(int $id): Response
    {
        

        return $this->render('qr_constroller/index.html.twig', [
            'id' => $id,
            //'form' => $form->createView(),
        ]);
        
    }
    /**
     * @Route("/qr/make/historia/{id}", name="make_qr_historia")
     */
    public function make(int $id, UrlGeneratorInterface $router, BuilderInterface $customQrCodeBuilder): Response
    {
        $url = $router->generate('cliente_historial', ['id'=>$id], urlGeneratorInterface::ABSOLUTE_URL); 

        $result = $customQrCodeBuilder->data($url)->size(400)->margin(20)->build();
        $response = new QrCodeResponse($result);
        $result->getDataUri();
        $result->getString();

        return $response;
        
    }
}
