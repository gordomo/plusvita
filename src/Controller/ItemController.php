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
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\String\Slugger\SluggerInterface;

/**
 * @Route("/item")
 */
class ItemController extends AbstractController
{
    /**
     * @Route("/", name="app_item_index")
     */
    public function index(EntityManagerInterface $em, Request $request): Response
    {
        $ubicacionId = $request->query->get('ubicacion');
        $items = $em->getRepository(Item::class)->findAll();
        $ubicaciones = $em->getRepository(Ubicacion::class)->findAll();
        $tiposItem = $em->getRepository(TipoItem::class)->findAll();

        // Filtrar por ubicación si corresponde
        if ($ubicacionId) {
            $items = array_filter($items, function($item) use ($ubicacionId) {
                return $item->getUbicacionActual() && $item->getUbicacionActual()->getId() == $ubicacionId;
            });
            // Pasar la ubicación seleccionada a la vista
            $ubicacion = null;
            foreach ($ubicaciones as $ubi) {
                if ($ubi->getId() == $ubicacionId) {
                    $ubicacion = $ubi;
                    break;
                }
            }
        } else {
            $ubicacion = null;
        }

        // Agrupar por ubicación y tipo
        $agrupados = [];
        foreach ($items as $item) {
            $ubicacionNombre = $item->getUbicacionActual() ? $item->getUbicacionActual()->getNombre() : 'Sin ubicación';
            $tipo = $item->getTipo() ? $item->getTipo()->getNombre() : 'Sin tipo';
            $detalle = $item->getNombre();
            $key = $ubicacionNombre . '|' . $tipo;
            if (!isset($agrupados[$key])) {
                $agrupados[$key] = [
                    'ubicacion' => $ubicacionNombre,
                    'tipo' => $tipo,
                    'detalles' => [],
                    'cantidad' => 0,
                ];
            }
            $agrupados[$key]['detalles'][] = $detalle;
            $agrupados[$key]['cantidad']++;
        }
        // Eliminar duplicados en detalles
        foreach ($agrupados as &$grupo) {
            $grupo['detalles'] = array_unique($grupo['detalles']);
        }
        unset($grupo);

        return $this->render('item/index.html.twig', [
            'items' => $items,
            'ubicaciones' => $ubicaciones,
            'tiposItem' => $tiposItem,
            'paginaImprimible' => true,
            'itemsAgrupados' => $agrupados,
            'ubicacion' => $ubicacion,
        ]);
    }

    /**
     * @Route("/ubicacion/{ubicacion}", name="app_item_index_ubicacion", methods={"GET"})
     */
    public function indexUbicacion(ItemRepository $itemRepository, UbicacionRepository $ubicacionRepository, TipoItemRepository $tipoItemRepository, Ubicacion $ubicacion): Response
    {
        $items = $itemRepository->findBy(['ubicacion_actual' => $ubicacion]);
        $ubicaciones = $ubicacionRepository->findAll();
        $tiposItem = $tipoItemRepository->findAll();
        
        // Organizar los ítems por tipo
        $itemsPorTipo = [];
        foreach ($items as $item) {
            $tipoId = $item->getTipo()->getId();
            $tipoNombre = $item->getTipo()->getNombre();
            
            if (!isset($itemsPorTipo[$tipoId])) {
                $itemsPorTipo[$tipoId] = [
                    'tipo' => $item->getTipo(),
                    'items' => [],
                    'totalItems' => 0
                ];
            }
            
            $itemsPorTipo[$tipoId]['items'][] = $item;
            $itemsPorTipo[$tipoId]['totalItems']++;
        }
        
        return $this->render('item/index_por_ubicacion.html.twig', [
            'items' => $items,
            'itemsPorTipo' => $itemsPorTipo,
            'ubicacion' => $ubicacion,
            'ubicaciones' => $ubicaciones,
            'tiposItem' => $tiposItem,
            'paginaImprimible' => true,
        ]);
    }

    /**
     * @Route("/tipo/{tipoItem}", name="app_item_index_tipo", methods={"GET"})
     */
    public function indexTipoItem(ItemRepository $itemRepository, TipoItemRepository $tipoItemRepository, UbicacionRepository $ubicacionRepository, TipoItem $tipoItem, Request $request): Response
    {
        $items = $itemRepository->findBy(['tipo' => $tipoItem]);
        $tiposItems = $tipoItemRepository->findAll();
        $ubicaciones = $ubicacionRepository->findAll();

        // Filtrar por ubicación si corresponde
        $ubicacionId = $request->query->get('ubicacion');
        if ($ubicacionId) {
            $items = array_filter($items, function($item) use ($ubicacionId) {
                return $item->getUbicacionActual() && $item->getUbicacionActual()->getId() == $ubicacionId;
            });
            $ubicacion = null;
            foreach ($ubicaciones as $ubi) {
                if ($ubi->getId() == $ubicacionId) {
                    $ubicacion = $ubi;
                    break;
                }
            }
        } else {
            $ubicacion = null;
        }

        // Agrupar por ubicación y tipo
        $agrupados = [];
        foreach ($items as $item) {
            $ubicacionNombre = $item->getUbicacionActual() ? $item->getUbicacionActual()->getNombre() : 'Sin ubicación';
            $tipo = $item->getTipo() ? $item->getTipo()->getNombre() : 'Sin tipo';
            $detalle = $item->getNombre();
            $key = $ubicacionNombre . '|' . $tipo;
            if (!isset($agrupados[$key])) {
                $agrupados[$key] = [
                    'ubicacion' => $ubicacionNombre,
                    'tipo' => $tipo,
                    'detalles' => [],
                    'cantidad' => 0,
                ];
            }
            $agrupados[$key]['detalles'][] = $detalle;
            $agrupados[$key]['cantidad']++;
        }
        foreach ($agrupados as &$grupo) {
            $grupo['detalles'] = array_unique($grupo['detalles']);
        }
        unset($grupo);

        return $this->render('item/index.html.twig', [
            'items' => $items,
            'tipoItem' => $tipoItem,
            'tiposItem' => $tiposItems,
            'ubicaciones' => $ubicaciones,
            'paginaImprimible' => true,
            'itemsAgrupados' => $agrupados,
            'ubicacion' => $ubicacion,
        ]);
    }

    /**
     * @Route("/new", name="app_item_new", methods={"GET", "POST"})
     */
    public function new(Request $request, EntityManagerInterface $entityManager, SluggerInterface $slugger, BuilderInterface $qrCodeBuilder): Response
    {
        $item = new Item();
        $form = $this->createForm(ItemType::class, $item);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Manejar la carga de la imagen
            $imagenFile = $form->get('imagen')->getData();
            if ($imagenFile) {
                $originalFilename = pathinfo($imagenFile->getClientOriginalName(), PATHINFO_FILENAME);
                $safeFilename = $slugger->slug($originalFilename);
                $newFilename = $safeFilename.'-'.uniqid().'.'.$imagenFile->guessExtension();

                try {
                    $imagenFile->move(
                        $this->getParameter('items_imagenes_directory'),
                        $newFilename
                    );
                    $item->setImagen($newFilename);
                } catch (FileException $e) {
                    // Mostrar mensaje de error si falla la carga
                    $this->addFlash('error', 'Ocurrió un error al subir la imagen: ' . $e->getMessage());
                    return $this->redirectToRoute('app_item_new');
                }
            }

            $item->setCodigoQr('');
            $entityManager->persist($item);

            // Registrar el movimiento inicial
            $movimiento = new Movimiento();
            $movimiento->setItem($item);
            $movimiento->setFecha(new \DateTime());
            $movimiento->setUbicacion($item->getUbicacionActual());
            $movimiento->setMotivo('Creación del ítem');
            $movimiento->setCantidad(1); // Siempre es 1 para ítems individuales
            $entityManager->persist($movimiento);

            $entityManager->flush();

            // Generar el código QR solo después de que el item tenga un ID
            $qrUrl = $this->generateUrl('app_item_show', ['id' => $item->getId()], UrlGeneratorInterface::ABSOLUTE_URL);
            $qrFilename = 'qr-item-' . $item->getId() . '.png';
            $qrPath = $this->getParameter('items_qr_directory') . '/' . $qrFilename;
            
            // Usar el servicio BuilderInterface inyectado para generar el QR
            $result = $qrCodeBuilder->data($qrUrl)
                ->size(200)
                ->margin(10)
                ->build();
            
            // Guardar el QR como archivo
            file_put_contents($qrPath, $result->getString());
            
            // Actualizar el item con la ruta del código QR
            $item->setCodigoQr($qrFilename);
            $entityManager->flush();

            $this->addFlash('success', 'El ítem ha sido creado correctamente.');
            return $this->redirectToRoute('app_item_index');
        }

        return $this->render('item/new.html.twig', [
            'item' => $item,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/{id}/edit", name="app_item_edit", methods={"GET", "POST"})
     */
    public function edit(Request $request, Item $item, EntityManagerInterface $entityManager, SluggerInterface $slugger): Response
    {
        $form = $this->createForm(ItemType::class, $item);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Manejar la imagen
            $imagenFile = $form->get('imagen')->getData();
            if ($imagenFile) {
                $originalFilename = pathinfo($imagenFile->getClientOriginalName(), PATHINFO_FILENAME);
                $safeFilename = $slugger->slug($originalFilename);
                $newFilename = $safeFilename.'-'.uniqid().'.'.$imagenFile->guessExtension();

                try {
                    $imagenFile->move(
                        $this->getParameter('items_imagenes_directory'),
                        $newFilename
                    );
                    
                    // Si había una imagen anterior, eliminarla
                    if ($item->getImagen()) {
                        $imagePath = $this->getParameter('items_imagenes_directory') . '/' . $item->getImagen();
                        if (file_exists($imagePath)) {
                            unlink($imagePath);
                        }
                    }
                    
                    $item->setImagen($newFilename);
                } catch (FileException $e) {
                    $this->addFlash('error', 'Ocurrió un error al subir la imagen: ' . $e->getMessage());
                }
            }

            // Registrar el movimiento si la ubicación cambió
            $uow = $entityManager->getUnitOfWork();
            $uow->computeChangeSets();
            $changeSet = $uow->getEntityChangeSet($item);
            
            if (isset($changeSet['ubicacion_actual'])) {
                $oldUbicacion = $changeSet['ubicacion_actual'][0];
                $newUbicacion = $changeSet['ubicacion_actual'][1];
                
                $movimiento = new Movimiento();
                $movimiento->setItem($item);
                $movimiento->setFecha(new \DateTime());
                $movimiento->setUbicacion($newUbicacion);
                $movimiento->setMotivo('Cambio de ubicación desde ' . ($oldUbicacion ? $oldUbicacion->getNombre() : 'sin ubicación'));
                $movimiento->setCantidad(1);
                $entityManager->persist($movimiento);
            }

            $entityManager->flush();
            $this->addFlash('success', 'El ítem ha sido actualizado correctamente.');
            
            return $this->redirectToRoute('app_item_show', ['id' => $item->getId()]);
        }

        return $this->render('item/edit.html.twig', [
            'item' => $item,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/{id}", name="app_item_show")
     */
    public function show(Item $item, EntityManagerInterface $entityManager): Response
    {
        // Obtener todas las ubicaciones para la vista
        $todasUbicaciones = $entityManager->getRepository(Ubicacion::class)->findAll();

        return $this->render('item/show.html.twig', [
            'item' => $item,
            'ubicaciones' => $todasUbicaciones,
        ]);
    }

    /**
     * @Route("/{id}/delete", name="app_item_delete", methods={"POST"})
     */
    public function delete(Request $request, Item $item, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$item->getId(), $request->request->get('_token'))) {
            // Si existe una imagen, eliminarla
            if ($item->getImagen()) {
                $imagePath = $this->getParameter('items_imagenes_directory') . '/' . $item->getImagen();
                if (file_exists($imagePath)) {
                    unlink($imagePath);
                }
            }
            
            // Si existe un código QR, eliminarlo
            if ($item->getCodigoQr()) {
                $qrPath = $this->getParameter('items_qr_directory') . '/' . $item->getCodigoQr();
                if (file_exists($qrPath)) {
                    unlink($qrPath);
                }
            }

            $entityManager->remove($item);
            $entityManager->flush();
            
            $this->addFlash('success', 'El ítem ha sido eliminado correctamente.');
        }

        return $this->redirectToRoute('app_item_index', [], Response::HTTP_SEE_OTHER);
    }

    /**
     * @Route("/qr/make/item/{id}", name="make_qr_item")
     */
    public function make(int $id, UrlGeneratorInterface $router, BuilderInterface $qrCodeBuilder): Response
    {
        $url = $router->generate('app_item_show', ['id'=>$id], urlGeneratorInterface::ABSOLUTE_URL); 

        $result = $qrCodeBuilder->data($url)->size(100)->margin(20)->build();
        $response = new QrCodeResponse($result);
        
        return $response;
    }

    /**
     * @Route("/{id}/move", name="app_item_move", methods={"POST"})
     */
    public function moveItem(Request $request, Item $item, EntityManagerInterface $entityManager): Response
    {
        $newLocationId = $request->request->get('location');
        $ubicacion = $entityManager->getRepository(Ubicacion::class)->find($newLocationId);
        
        if (!$ubicacion) {
            $this->addFlash('danger', 'La ubicación seleccionada no existe.');
            return $this->redirectToRoute('app_item_show', ['id' => $item->getId()]);
        }
        
        // Verificar que la ubicación no sea la misma
        if ($item->getUbicacionActual()->getId() === (int) $newLocationId) {
            $this->addFlash('warning', 'La nueva ubicación es igual a la actual. No se realizó el movimiento.');
            return $this->redirectToRoute('app_item_show', ['id' => $item->getId()]);
        }
        
        $oldUbicacion = $item->getUbicacionActual();
        $item->setUbicacionActual($ubicacion);
        
        // Registrar el movimiento
        $movimiento = new Movimiento();
        $movimiento->setItem($item);
        $movimiento->setFecha(new \DateTime());
        $movimiento->setUbicacion($ubicacion);
        $movimiento->setMotivo('Cambio de ubicación desde ' . $oldUbicacion->getNombre() . ' a ' . $ubicacion->getNombre());
        $movimiento->setCantidad(1);
        $entityManager->persist($movimiento);
        
        $entityManager->flush();
        
        $this->addFlash('success', 'El ítem ha sido movido correctamente a la ubicación ' . $ubicacion->getNombre());
        return $this->redirectToRoute('app_item_show', ['id' => $item->getId()]);
    }
}
