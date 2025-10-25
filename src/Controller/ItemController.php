<?php
// src/Controller/ItemController.php

namespace App\Controller;

use App\Entity\Item;
use App\Entity\Movimiento;
use App\Entity\NotaItem;
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
        $searchTerm = $request->query->get('search', '');
        
        // Obtener items con búsqueda si se proporciona
        if (!empty($searchTerm)) {
            $items = $em->getRepository(Item::class)->searchItems($searchTerm);
        } else {
            $items = $em->getRepository(Item::class)->findAll();
        }
        
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
            'searchTerm' => $searchTerm,
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
            'searchTerm' => $request->query->get('search', ''),
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
     * @Route("/reporte-ubicaciones", name="app_item_report_selector", methods={"GET"})
     */
    public function reportSelector(EntityManagerInterface $em): Response
    {
        $ubicaciones = $em->getRepository(Ubicacion::class)->findAll();
        
        return $this->render('item/report_selector.html.twig', [
            'ubicaciones' => $ubicaciones,
        ]);
    }

    /**
     * @Route("/print", name="app_item_print", methods={"GET", "POST"})
     */
    public function print(Request $request, EntityManagerInterface $em): Response
    {
        $tipoId = $request->query->get('tipo');
        $ubicacionId = $request->query->get('ubicacion');
        $ubicacionesIds = $request->query->get('ubicaciones', []);
        $searchTerm = $request->query->get('search', '');
        $reportType = $request->query->get('report_type', 'items'); // 'items' o 'maintenance'
        
        // Obtener items con filtros
        $items = $em->getRepository(Item::class)->findAll();
        
        // Aplicar filtros
        if (!empty($searchTerm)) {
            $items = $em->getRepository(Item::class)->searchItems($searchTerm);
        }
        
        if ($tipoId) {
            $items = array_filter($items, function($item) use ($tipoId) {
                return $item->getTipo() && $item->getTipo()->getId() == $tipoId;
            });
        }
        
        if ($ubicacionId) {
            $items = array_filter($items, function($item) use ($ubicacionId) {
                return $item->getUbicacionActual() && $item->getUbicacionActual()->getId() == $ubicacionId;
            });
        }
        
        // Obtener datos para filtros
        $ubicaciones = $em->getRepository(Ubicacion::class)->findAll();
        $tiposItem = $em->getRepository(TipoItem::class)->findAll();
        
        if ($reportType === 'maintenance') {
            // Reporte de mantenimiento por ubicaciones seleccionadas
            $ubicacionesSeleccionadas = [];
            if (!empty($ubicacionesIds)) {
                $ubicacionesSeleccionadas = $em->getRepository(Ubicacion::class)->findBy(['id' => $ubicacionesIds]);
            } elseif ($ubicacionId) {
                // Mantener compatibilidad con el sistema anterior
                $ubicacionesSeleccionadas = [$em->getRepository(Ubicacion::class)->find($ubicacionId)];
            }
            
            // Obtener todos los movimientos de las ubicaciones seleccionadas (últimos 6 meses)
            $fechaDesde = new \DateTime('-6 months');
            $movimientos = $em->getRepository(Movimiento::class)->createQueryBuilder('m')
                ->leftJoin('m.item', 'i')
                ->where('m.fecha >= :fechaDesde')
                ->setParameter('fechaDesde', $fechaDesde);
                
            if (!empty($ubicacionesSeleccionadas)) {
                $ubicacionIds = array_map(function($u) { return $u->getId(); }, $ubicacionesSeleccionadas);
                $movimientos->andWhere('i.ubicacion_actual IN (:ubicaciones)')
                           ->setParameter('ubicaciones', $ubicacionIds);
            }
            
            $movimientos = $movimientos->orderBy('m.fecha', 'DESC')
                                     ->getQuery()
                                     ->getResult();
            
            return $this->render('item/maintenance_report.html.twig', [
                'movimientos' => $movimientos,
                'ubicaciones' => $ubicaciones,
                'tiposItem' => $tiposItem,
                'ubicacionesSeleccionadas' => $ubicacionesSeleccionadas,
                'ubicacionId' => $ubicacionId,
                'searchTerm' => $searchTerm,
                'fechaDesde' => $fechaDesde,
            ]);
        } else {
            // Reporte normal de items
            // Obtener últimos 3 movimientos para cada item
            foreach ($items as $item) {
                $movimientos = $em->getRepository(Movimiento::class)->findBy(
                    ['item' => $item],
                    ['fecha' => 'DESC'],
                    3
                );
                $item->ultimosMovimientos = $movimientos;
            }
            
            return $this->render('item/print.html.twig', [
                'items' => $items,
                'ubicaciones' => $ubicaciones,
                'tiposItem' => $tiposItem,
                'tipoId' => $tipoId,
                'ubicacionId' => $ubicacionId,
                'searchTerm' => $searchTerm,
            ]);
        }
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

    
    /**
     * @Route("/{id}/nota/agregar", name="app_item_add_nota", methods={"POST"})
     */
    public function addNota(Request $request, Item $item, EntityManagerInterface $entityManager): Response
    {
        $contenido = $request->request->get('contenido');
        
        if (!$contenido) {
            $this->addFlash('error', 'El contenido de la nota no puede estar vacío.');
            return $this->redirectToRoute('app_item_show', ['id' => $item->getId()]);
        }
        
        $nota = new NotaItem();
        $nota->setItem($item);
        $nota->setContenido($contenido);
        $nota->setUsuario($this->getUser());
        
        $entityManager->persist($nota);
        $entityManager->flush();
        
        $this->addFlash('success', 'La nota ha sido agregada correctamente.');
        return $this->redirectToRoute('app_item_show', ['id' => $item->getId()]);
    }

    /**
     * @Route("/{id}/nota/{notaId}/eliminar", name="app_item_delete_nota", methods={"POST"})
     */
    public function deleteNota(Request $request, Item $item, int $notaId, EntityManagerInterface $entityManager): Response
    {
        $nota = $entityManager->getRepository(NotaItem::class)->find($notaId);
        
        if (!$nota) {
            $this->addFlash('error', 'La nota no existe.');
            return $this->redirectToRoute('app_item_show', ['id' => $item->getId()]);
        }
        
        if ($nota->getItem() !== $item) {
            $this->addFlash('error', 'La nota no pertenece a este item.');
            return $this->redirectToRoute('app_item_show', ['id' => $item->getId()]);
        }
        
        $entityManager->remove($nota);
        $entityManager->flush();
        
        $this->addFlash('success', 'La nota ha sido eliminada correctamente.');
        return $this->redirectToRoute('app_item_show', ['id' => $item->getId()]);
    }
    /**
     * @Route("/{id}/duplicate", name="app_item_duplicate", methods={"GET", "POST"})
     */
    public function duplicate(Request $request, Item $item, EntityManagerInterface $entityManager, BuilderInterface $qrCodeBuilder): Response
    {
        if ($request->isMethod('POST')) {
            $cantidad = intval($request->request->get('cantidad', 1));
            $nombre = $request->request->get('nombre', $item->getNombre());
            $identificador = $request->request->get('identificador', $item->getIdentificador());
            $tipoId = $request->request->get('tipo');
            $ubicacionId = $request->request->get('ubicacion');
            
            if ($cantidad < 1) {
                $this->addFlash('warning', 'La cantidad debe ser al menos 1.');
                return $this->redirectToRoute('app_item_show', ['id' => $item->getId()]);
            }
            
            // Obtener tipo y ubicación seleccionados
            $tipo = $tipoId ? $entityManager->getRepository(TipoItem::class)->find($tipoId) : $item->getTipo();
            $ubicacion = $ubicacionId ? $entityManager->getRepository(Ubicacion::class)->find($ubicacionId) : $item->getUbicacionActual();
            
            if (!$tipo || !$ubicacion) {
                $this->addFlash('error', 'Debe seleccionar un tipo y una ubicación válidos.');
                return $this->redirectToRoute('app_item_duplicate', ['id' => $item->getId()]);
            }
            
            $itemsCreados = 0;
            
            for ($i = 0; $i < $cantidad; $i++) {
                $nuevoItem = new Item();
                $nuevoItem->setNombre($nombre);
                $nuevoItem->setTipo($tipo);
                $nuevoItem->setUbicacionActual($ubicacion);
                
                // Reusamos la misma imagen si existe
                if ($item->getImagen()) {
                    $nuevoItem->setImagen($item->getImagen());
                }
                
                // Usamos el identificador tal como se especificó en el formulario
                if ($identificador) {
                    $nuevoItem->setIdentificador($identificador);
                }
                
                $nuevoItem->setCodigoQr('');
                $entityManager->persist($nuevoItem);
                
                // Crear movimiento inicial
                $movimiento = new Movimiento();
                $movimiento->setItem($nuevoItem);
                $movimiento->setFecha(new \DateTime());
                $movimiento->setUbicacion($nuevoItem->getUbicacionActual());
                $movimiento->setMotivo('Clonado a partir del ítem #' . $item->getId());
                $movimiento->setCantidad(1);
                $entityManager->persist($movimiento);
                
                $itemsCreados++;
            }
            
            $entityManager->flush();
            
            // Generar QR para cada nuevo ítem
            $items = $entityManager->getRepository(Item::class)->findBy(['codigo_qr' => '']);
            $qrDirectory = $this->getParameter('items_qr_directory');
            
            // Asegurar que el directorio existe
            if (!is_dir($qrDirectory)) {
                mkdir($qrDirectory, 0755, true);
            }
            
            foreach ($items as $nuevoItem) {
                $qrUrl = $this->generateUrl('app_item_show', ['id' => $nuevoItem->getId()], UrlGeneratorInterface::ABSOLUTE_URL);
                $qrFilename = 'qr-item-' . $nuevoItem->getId() . '.png';
                $qrPath = $qrDirectory . '/' . $qrFilename;
                
                try {
                    $result = $qrCodeBuilder->data($qrUrl)
                        ->size(200)
                        ->margin(10)
                        ->build();
                    
                    if (file_put_contents($qrPath, $result->getString()) === false) {
                        $this->addFlash('warning', 'No se pudo generar el código QR para el ítem #' . $nuevoItem->getId());
                    } else {
                        $nuevoItem->setCodigoQr($qrFilename);
                    }
                } catch (\Exception $e) {
                    $this->addFlash('warning', 'Error al generar código QR para el ítem #' . $nuevoItem->getId() . ': ' . $e->getMessage());
                }
            }
            
            $entityManager->flush();
            
            $this->addFlash('success', "Se han creado $itemsCreados clones del ítem correctamente.");
            return $this->redirectToRoute('app_item_index');
        }
        
        // Obtener ubicaciones y tipos disponibles para los dropdowns
        $ubicaciones = $entityManager->getRepository(Ubicacion::class)->findAll();
        $tiposItem = $entityManager->getRepository(TipoItem::class)->findAll();
        
        return $this->render('item/duplicate.html.twig', [
            'item' => $item,
            'ubicaciones' => $ubicaciones,
            'tiposItem' => $tiposItem,
        ]);
    }

    /**
     * @Route("/{id}", name="app_item_show", methods={"GET"})
     */
    public function show(Item $item, EntityManagerInterface $entityManager): Response
    {
        // Obtener todas las ubicaciones para la vista
        $todasUbicaciones = $entityManager->getRepository(Ubicacion::class)->findAll();
        
        // Obtener las notas del item ordenadas por fecha de creación descendente
        $notas = $entityManager->getRepository(NotaItem::class)->findBy(
            ['item' => $item],
            ['fecha_creacion' => 'DESC']
        );

        return $this->render('item/show.html.twig', [
            'item' => $item,
            'ubicaciones' => $todasUbicaciones,
            'notas' => $notas,
        ]);
    }
}
