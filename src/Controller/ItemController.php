<?php
// src/Controller/ItemController.php

namespace App\Controller;

use App\Entity\Item;
use App\Entity\Movimiento;
use App\Entity\TipoItem;
use App\Entity\Ubicacion;
use App\Form\ItemType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Endroid\QrCode\Builder\BuilderInterface;
use Endroid\QrCodeBundle\Response\QrCodeResponse;
use App\Repository\ItemRepository;
use App\Repository\TipoItemRepository;
use App\Repository\UbicacionRepository;

/**
 * @Route("/item")
 */
class ItemController extends AbstractController
{
    /**
     * @Route("/", name="app_item_index")
     */
    public function index(EntityManagerInterface $em): Response
    {
        $items = $em->getRepository(Item::class)->findAll();
        $ubicaciones = $em->getRepository(Ubicacion::class)->findAll();

        return $this->render('item/index.html.twig', [
            'items' => $items,
            'ubicaciones' => $ubicaciones,
        ]);
    }

    /**
     * @Route("/ubicacion/{ubicacion}", name="app_item_index_ubicacion", methods={"GET"})
     */
    public function indexUbicacion(ItemRepository $itemRepository, UbicacionRepository $ubicacionRepository, Ubicacion $ubicacion): Response
    {
        $items = $itemRepository->findBy(['ubicacion_actual' => $ubicacion]);
        $ubicaciones = $ubicacionRepository->findAll();
        
        return $this->render('item/index.html.twig', [
            'items' => $items,
            'ubicacion' => $ubicacion,
            'ubicaciones' => $ubicaciones,
        ]);
    }
    /**
     * @Route("/tipo/{tipoItem}", name="app_item_index_tipo", methods={"GET"})
     */
    public function indexTipoItem(ItemRepository $itemRepository, TipoItemRepository $tipoItemRepository, UbicacionRepository $ubicacionRepository, TipoItem $tipoItem): Response
    {
        $items = $itemRepository->findBy(['tipo' => $tipoItem]);
        $tiposItems = $tipoItemRepository->findAll();
        $ubicaciones = $ubicacionRepository->findAll();
        
        return $this->render('item/index.html.twig', [
            'items' => $items,
            'tipoItem' => $tipoItem,
            'tiposItems' => $tiposItems,
            'ubicaciones' => $ubicaciones,
        ]);
    }

    /**
     * @Route("/new", name="app_item_new")
     */
    public function new(Request $request, EntityManagerInterface $em): Response
    {
        $item = new Item();
        $form = $this->createForm(ItemType::class, $item);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $item->setCodigoQr('');
            $em->persist($item);

            $movimiento = new Movimiento();
            $movimiento->setItem($item);
            $movimiento->setFecha(new \DateTime());
            $movimiento->setUbicacion($item->getUbicacionActual());
            $movimiento->setMotivo('creación');
            $movimiento->setCantidad($item->getCantidad());
            // Guarda el movimiento
            $em->persist($movimiento);

            $em->flush();

            return $this->redirectToRoute('app_item_index');
        }

        return $this->render('item/_new.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/{id}/edit", name="app_item_edit")
     */
    public function edit(Request $request, Item $item, EntityManagerInterface $em): Response
    {
        // Obtiene la ubicación y cantidad anteriores para compararlas
        $ubicacionAnterior = $item->getUbicacionActual();
        $cantidadAnterior = $item->getCantidad();

        $form = $this->createForm(ItemType::class, $item);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            $cantidadNueva = $form->get('cantidad')->getData();
            $ubicacionNueva = $form->get('ubicacion_actual')->getData();
            
            // Registra un movimiento solo si hubo cambios en la ubicación o cantidad
            if ($ubicacionAnterior !== $ubicacionNueva || $cantidadAnterior !== $cantidadNueva) {
                $movimiento = new Movimiento();
                $movimiento->setItem($item);
                $movimiento->setFecha(new \DateTime());
                $movimiento->setUbicacion($ubicacionNueva);
                $movimiento->setCantidad($cantidadNueva);
                $motivo = $ubicacionAnterior !== $ubicacionNueva ? 'Nueva ubicación' : '';
                $motivo = $cantidadAnterior !== $cantidadNueva ? (($motivo) ? $motivo . ' y cantidad' : 'Nueva cantidad') : '';


                $movimiento->setMotivo($motivo);

                // Guarda el movimiento
                $em->persist($movimiento);
            }

            // Guarda el item actualizado
            $em->flush();

            return $this->redirectToRoute('app_item_index');
        }

        return $this->render('item/_new.html.twig', [
            'form' => $form->createView(),
            'item' => $item,
        ]);
    }

    /**
     * @Route("/{id}", name="app_item_show")
     */
    public function show(Item $item): Response
    {
        return $this->render('item/show.html.twig', [
            'item' => $item,
        ]);
    }

    /**
     * @Route("/{id}/delete", name="app_item_delete", methods={"POST"})
     */
    public function delete(Request $request, Item $item, EntityManagerInterface $em): Response
    {
        if ($this->isCsrfTokenValid('delete'.$item->getId(), $request->request->get('_token'))) {
            $em->remove($item);
            $em->flush();
        }

        return $this->redirectToRoute('app_item_index');
    }

    /**
     * @Route("/qr/make/item/{id}", name="make_qr_item")
     */
    public function make(int $id, UrlGeneratorInterface $router, BuilderInterface $customQrCodeBuilder): Response
    {
        $url = $router->generate('app_item_show', ['id'=>$id], urlGeneratorInterface::ABSOLUTE_URL); 

        $result = $customQrCodeBuilder->data($url)->size(100)->margin(20)->build();
        $response = new QrCodeResponse($result);
        $result->getDataUri();
        $result->getString();

        return $response;
        
    }

    /**
     * @Route("/items/move", name="app_item_move", methods={"POST"})
     */
    public function moveItem(Request $request, ItemRepository $itemRepository, UbicacionRepository $ubicacionRepository, EntityManagerInterface $em ): Response {
        $itemId = $request->request->get('item_id');
        $newQuantity = (int) $request->request->get('quantity');
        $newLocationId = $request->request->get('location');

        // Obtener el item original
        $item = $itemRepository->find($itemId);
        $currentLocation = $item->getUbicacionActual();

        // Validar que la ubicación no sea la misma
        if ($currentLocation->getId() === (int) $newLocationId) {
            $this->addFlash('danger', 'La nueva ubicación es igual a la actual. No se realizó el movimiento.');
            return $this->redirectToRoute('app_item_index');
        }

        // Validar cantidad
        if ($item->getCantidad() < $newQuantity) {
            $this->addFlash('danger', 'La cantidad a mover excede la disponible.');
            return $this->redirectToRoute('app_item_index');
        }

        // Obtener la nueva ubicación
        $newLocation = $ubicacionRepository->find($newLocationId);

        // Crear un nuevo registro para la cantidad movida
        $newItem = clone $item;
        $newItem->setCantidad($newQuantity);
        $newItem->setUbicacionActual($newLocation);

        // Actualizar la cantidad del item original
        $item->setCantidad($item->getCantidad() - $newQuantity);

        // Registrar los movimientos en el historial
        $this->registerMovement($em, $item, $newQuantity, $currentLocation, $newLocation, 'Cantidad reducida por movimiento: se movieron ' . $newQuantity . ' a ' . $newLocation->getNombre());
        $this->registerMovement($em, $newItem, $newQuantity, $currentLocation, $newLocation, 'Nuevo registro por movimiento. Estos items vienen de: ' . $currentLocation->getNombre());

        // Persistir los cambios
        $em->persist($item);
        $em->persist($newItem);
        $em->flush();

        $this->addFlash('success', 'Item movido correctamente.');
        return $this->redirectToRoute('app_item_index');
    }


    private function registerMovement(
        EntityManagerInterface $em,
        Item $item,
        int $quantity,
        Ubicacion $fromLocation,
        Ubicacion $toLocation,
        string $reason
    ): void {
        $movement = new Movimiento();
        $movement->setItem($item);
        $movement->setUbicacion($toLocation);
        $movement->setCantidad($quantity);
        $movement->setMotivo($reason);
        $movement->setFecha(new \DateTime());
    
        $em->persist($movement);
    }
    

}
