<?php

namespace App\Controller;

use App\Entity\UserContract;
use App\Form\UserContractType;
use App\Repository\UserContractRepository;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/contratos")
 */
class UserContractController extends AbstractController
{
    /**
     * @Route("/", name="user_contract_index", methods={"GET"})
     */
    public function index(UserContractRepository $contractRepository): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $contracts = $contractRepository->createQueryBuilder('uc')
            ->leftJoin('uc.user', 'u')
            ->addSelect('u')
            ->where('uc.isActive = 1')
            ->orderBy('uc.vtoContrato', 'ASC')
            ->getQuery()
            ->getResult();

        return $this->render('user_contract/index.html.twig', [
            'contracts' => $contracts,
        ]);
    }

    /**
     * @Route("/usuario/{id}", name="user_contract_by_user", methods={"GET"})
     */
    public function byUser($id, UserRepository $userRepository, UserContractRepository $contractRepository): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $user = $userRepository->find($id);
        if (!$user) {
            throw $this->createNotFoundException('Usuario no encontrado');
        }

        $contracts = $contractRepository->findByUser($id);

        return $this->render('user_contract/by_user.html.twig', [
            'user' => $user,
            'contracts' => $contracts,
        ]);
    }

    /**
     * @Route("/nuevo/{userId}", name="user_contract_new", methods={"GET","POST"})
     */
    public function new(Request $request, $userId, UserRepository $userRepository): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $user = $userRepository->find($userId);
        if (!$user) {
            throw $this->createNotFoundException('Usuario no encontrado');
        }

        $contract = new UserContract();
        $contract->setUser($user);

        $form = $this->createForm(UserContractType::class, $contract);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Si se marca como activo, desactivar otros contratos del usuario
            if ($contract->getIsActive()) {
                $entityManager = $this->getDoctrine()->getManager();
                $contractRepository = $entityManager->getRepository(UserContract::class);
                $contractRepository->deactivateUserContracts($userId);
            }

            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->persist($contract);
            $entityManager->flush();

            $this->addFlash('success', 'Contrato creado exitosamente.');

            return $this->redirectToRoute('user_contract_by_user', ['id' => $userId]);
        }

        return $this->render('user_contract/new.html.twig', [
            'contract' => $contract,
            'user' => $user,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/{id}", name="user_contract_show", methods={"GET"})
     */
    public function show(UserContract $contract): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        return $this->render('user_contract/show.html.twig', [
            'contract' => $contract,
        ]);
    }

    /**
     * @Route("/{id}/editar", name="user_contract_edit", methods={"GET","POST"})
     */
    public function edit(Request $request, UserContract $contract): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $form = $this->createForm(UserContractType::class, $contract);
        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            if ($form->isValid()) {
                $entityManager = $this->getDoctrine()->getManager();
                
                // Si se marca como activo, desactivar otros contratos del usuario
                if ($contract->getIsActive()) {
                    $contractRepository = $entityManager->getRepository(UserContract::class);
                    // Desactivar todos los contratos del usuario
                    $contractRepository->deactivateUserContracts($contract->getUser()->getId());
                    // Reactivar este contrato específicamente
                    $contract->setIsActive(true);
                }

                $contract->setUpdatedAt(new \DateTime());
                $entityManager->flush();

                $this->addFlash('success', 'Contrato actualizado exitosamente.');

                return $this->redirectToRoute('user_contract_by_user', ['id' => $contract->getUser()->getId()]);
            } else {
                // Mostrar errores específicos del formulario
                foreach ($form->getErrors(true) as $error) {
                    $this->addFlash('error', $error->getMessage());
                }
            }
        }

        return $this->render('user_contract/edit.html.twig', [
            'contract' => $contract,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/{id}/eliminar", name="user_contract_delete", methods={"POST"})
     */
    public function delete(Request $request, UserContract $contract): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        if ($this->isCsrfTokenValid('delete'.$contract->getId(), $request->request->get('_token'))) {
            $userId = $contract->getUser()->getId();
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->remove($contract);
            $entityManager->flush();

            $this->addFlash('success', 'Contrato eliminado exitosamente.');

            return $this->redirectToRoute('user_contract_by_user', ['id' => $userId]);
        }

        return $this->redirectToRoute('user_contract_index');
    }

    /**
     * @Route("/{id}/desactivar", name="user_contract_deactivate", methods={"POST"})
     */
    public function deactivate(Request $request, UserContract $contract): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        if ($this->isCsrfTokenValid('deactivate'.$contract->getId(), $request->request->get('_token'))) {
            $contract->setIsActive(false);
            $contract->setUpdatedAt(new \DateTime());
            $this->getDoctrine()->getManager()->flush();

            $this->addFlash('success', 'Contrato desactivado exitosamente.');

            return $this->redirectToRoute('user_contract_by_user', ['id' => $contract->getUser()->getId()]);
        }

        return $this->redirectToRoute('user_contract_index');
    }

    /**
     * @Route("/{id}/activar", name="user_contract_activate", methods={"POST"})
     */
    public function activate(Request $request, UserContract $contract): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        if ($this->isCsrfTokenValid('activate'.$contract->getId(), $request->request->get('_token'))) {
            // Desactivar otros contratos del usuario
            $entityManager = $this->getDoctrine()->getManager();
            $contractRepository = $entityManager->getRepository(UserContract::class);
            $contractRepository->deactivateUserContracts($contract->getUser()->getId());

            // Activar este contrato
            $contract->setIsActive(true);
            $contract->setUpdatedAt(new \DateTime());
            $entityManager->flush();

            $this->addFlash('success', 'Contrato activado exitosamente.');

            return $this->redirectToRoute('user_contract_by_user', ['id' => $contract->getUser()->getId()]);
        }

        return $this->redirectToRoute('user_contract_index');
    }

    /**
     * @Route("/vencimientos/mes", name="user_contract_expiring", methods={"GET"})
     */
    public function expiring(UserContractRepository $contractRepository): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $expiringThisMonth = $contractRepository->findExpiringThisMonth();
        $expired = $contractRepository->findExpired();

        return $this->render('user_contract/expiring.html.twig', [
            'expiring_this_month' => $expiringThisMonth,
            'expired' => $expired,
        ]);
    }
}
