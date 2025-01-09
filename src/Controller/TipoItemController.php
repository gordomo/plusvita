<?php

namespace App\Controller;

use App\Entity\TipoItem;
use App\Form\TipoItemType;
use App\Repository\TipoItemRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/tipo/item")
 */
class TipoItemController extends AbstractController
{
    /**
     * @Route("/", name="app_tipo_item_index", methods={"GET"})
     */
    public function index(TipoItemRepository $tipoItemRepository): Response
    {
        return $this->render('tipo_item/index.html.twig', [
            'tipo_items' => $tipoItemRepository->findAll(),
        ]);
    }

    /**
     * @Route("/new", name="app_tipo_item_new", methods={"GET", "POST"})
     */
    public function new(Request $request, TipoItemRepository $tipoItemRepository): Response
    {
        $tipoItem = new TipoItem();
        $form = $this->createForm(TipoItemType::class, $tipoItem);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $tipoItemRepository->add($tipoItem);
            return $this->redirectToRoute('app_tipo_item_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('tipo_item/new.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/ubicacion/validate", name="app_tipo_item_validate")
     */
    public function validate(Request $request, TipoItemRepository $ubicacionRepository): JsonResponse
    {
        $nombre = $request->query->get('nombre');
        $ubicacionExistente = $ubicacionRepository->findOneBy(['nombre' => $nombre]);

        return new JsonResponse(['exists' => $ubicacionExistente !== null]);
    }

    /**
     * @Route("/{id}", name="app_tipo_item_show", methods={"GET"})
     */
    public function show(TipoItem $tipoItem): Response
    {
        return $this->render('tipo_item/show.html.twig', [
            'tipo_item' => $tipoItem,
        ]);
    }

    /**
     * @Route("/{id}/edit", name="app_tipo_item_edit", methods={"GET", "POST"})
     */
    public function edit(Request $request, TipoItem $tipoItem, TipoItemRepository $tipoItemRepository): Response
    {
        $form = $this->createForm(TipoItemType::class, $tipoItem);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $tipoItemRepository->add($tipoItem);
            return $this->redirectToRoute('app_tipo_item_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('tipo_item/new.html.twig', [
            'tipo_item' => $tipoItem,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/{id}", name="app_tipo_item_delete", methods={"POST"})
     */
    public function delete(Request $request, TipoItem $tipoItem, TipoItemRepository $tipoItemRepository): Response
    {
        if ($this->isCsrfTokenValid('delete'.$tipoItem->getId(), $request->request->get('_token'))) {
            $tipoItemRepository->remove($tipoItem);
        }

        return $this->redirectToRoute('app_tipo_item_index', [], Response::HTTP_SEE_OTHER);
    }
}
