<?php

namespace App\Controller;

use App\Entity\User;
use App\Entity\UserFirma;
use App\Repository\UserFirmaRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\String\Slugger\SluggerInterface;

/**
 * @Route("/user-firma")
 */
class UserFirmaController extends AbstractController
{
    private $userFirmaRepository;
    private $entityManager;
    private $security;
    private $slugger;
    private $signatureUploadDirectory;

    public function __construct(
        UserFirmaRepository $userFirmaRepository,
        EntityManagerInterface $entityManager,
        Security $security,
        SluggerInterface $slugger,
        string $firmasDirectory
    ) {
        $this->userFirmaRepository = $userFirmaRepository;
        $this->entityManager = $entityManager;
        $this->security = $security;
        $this->slugger = $slugger;
        $this->signatureUploadDirectory = $firmasDirectory;
    }

    /**
     * @Route("/{userId}", name="user_firma_index", methods={"GET"})
     */
    public function index(int $userId): Response
    {
        $user = $this->entityManager->getRepository(User::class)->find($userId);
        
        if (!$user) {
            throw $this->createNotFoundException('Usuario no encontrado');
        }

        // Verificar permisos: el usuario puede ver sus propias firmas o si tiene permiso para gestionar firmas de otros
        $currentUser = $this->security->getUser();
        if ($currentUser->getId() !== $userId && !$currentUser->hasPermission('firma.manage_others')) {
            throw $this->createAccessDeniedException('No tienes permiso para acceder a este recurso');
        }

        $firmas = $this->userFirmaRepository->findByUser($userId);

        return $this->render('user_firma/index.html.twig', [
            'user' => $user,
            'firmas' => $firmas,
        ]);
    }

    /**
     * @Route("/{userId}/upload", name="user_firma_upload", methods={"POST"})
     */
    public function upload(int $userId, Request $request): Response
    {
        $user = $this->entityManager->getRepository(User::class)->find($userId);
        
        if (!$user) {
            throw $this->createNotFoundException('Usuario no encontrado');
        }

        // Verificar permisos
        $currentUser = $this->security->getUser();
        if ($currentUser->getId() !== $userId && !$currentUser->hasPermission('firma.manage_others')) {
            throw $this->createAccessDeniedException('No tienes permiso para realizar esta acción');
        }

        // Verificar permiso de creación
        if (!$currentUser->hasPermission('firma.create')) {
            throw $this->createAccessDeniedException('No tienes permiso para crear firmas');
        }

        $uploadedFile = $request->files->get('signature_file');
        
        if (!$uploadedFile) {
            $this->addFlash('error', 'Debe seleccionar un archivo');
            return $this->redirectToRoute('user_firma_index', ['userId' => $userId]);
        }

        // Validar que sea una imagen
        $mimeType = $uploadedFile->getMimeType();
        $allowedMimes = ['image/png', 'image/jpeg', 'image/jpg', 'image/gif'];
        
        if (!in_array($mimeType, $allowedMimes)) {
            $this->addFlash('error', 'El archivo debe ser una imagen válida (PNG, JPG, GIF)');
            return $this->redirectToRoute('user_firma_index', ['userId' => $userId]);
        }

        try {
            $fileName = $this->handleFileUpload($uploadedFile);
            
            // Desactivar todas las firmas anteriores del usuario
            foreach ($user->getFirmas() as $existingFirma) {
                $existingFirma->setIsActive(false);
                $this->entityManager->persist($existingFirma);
            }
            
            // Crear la nueva firma y activarla automáticamente
            $firma = new UserFirma();
            $firma->setUser($user);
            $firma->setFileName($uploadedFile->getClientOriginalName());
            $firma->setFilePath($fileName);
            $firma->setIsActive(true); // Activar la nueva firma por defecto
            
            $this->entityManager->persist($firma);
            $this->entityManager->flush();

            $this->addFlash('success', 'Firma cargada y activada exitosamente');
        } catch (\Exception $e) {
            $this->addFlash('error', 'Error al cargar la firma: ' . $e->getMessage());
        }

        return $this->redirectToRoute('user_firma_index', ['userId' => $userId]);
    }

    /**
     * @Route("/{firmaId}/activate", name="user_firma_activate", methods={"POST"})
     */
    public function activate(int $firmaId, Request $request): Response
    {
        $firma = $this->userFirmaRepository->find($firmaId);
        
        if (!$firma) {
            throw $this->createNotFoundException('Firma no encontrada');
        }

        // Verificar permisos
        $currentUser = $this->security->getUser();
        if ($currentUser->getId() !== $firma->getUser()->getId() && !$currentUser->hasPermission('firma.manage_others')) {
            throw $this->createAccessDeniedException('No tienes permiso para realizar esta acción');
        }

        if (!$currentUser->hasPermission('firma.update')) {
            throw $this->createAccessDeniedException('No tienes permiso para actualizar firmas');
        }

        // Desactivar todas las otras firmas del usuario
        foreach ($firma->getUser()->getFirmas() as $otherFirma) {
            $otherFirma->setIsActive(false);
        }

        // Activar la firma seleccionada
        $firma->setIsActive(true);
        $firma->setUpdatedAt(new \DateTime());

        $this->entityManager->flush();

        $this->addFlash('success', 'Firma activada exitosamente');

        return $this->redirectToRoute('user_firma_index', ['userId' => $firma->getUser()->getId()]);
    }

    /**
     * @Route("/{firmaId}/delete", name="user_firma_delete", methods={"POST"})
     */
    public function delete(int $firmaId, Request $request): Response
    {
        $firma = $this->userFirmaRepository->find($firmaId);
        
        if (!$firma) {
            throw $this->createNotFoundException('Firma no encontrada');
        }

        // Verificar permisos
        $currentUser = $this->security->getUser();
        if ($currentUser->getId() !== $firma->getUser()->getId() && !$currentUser->hasPermission('firma.manage_others')) {
            throw $this->createAccessDeniedException('No tienes permiso para realizar esta acción');
        }

        if (!$currentUser->hasPermission('firma.delete')) {
            throw $this->createAccessDeniedException('No tienes permiso para eliminar firmas');
        }

        $userId = $firma->getUser()->getId();
        
        // Eliminar archivo del servidor
        try {
            $filePath = $this->signatureUploadDirectory . '/' . $firma->getFilePath();
            if (file_exists($filePath)) {
                unlink($filePath);
            }
        } catch (\Exception $e) {
            $this->addFlash('warning', 'Firma eliminada de la BD pero no se pudo eliminar el archivo: ' . $e->getMessage());
        }

        $this->entityManager->remove($firma);
        $this->entityManager->flush();

        $this->addFlash('success', 'Firma eliminada exitosamente');

        return $this->redirectToRoute('user_firma_index', ['userId' => $userId]);
    }

    /**
     * Handle file upload
     */
    private function handleFileUpload(UploadedFile $file): string
    {
        $originalFilename = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
        $safeFilename = $this->slugger->slug($originalFilename);
        $fileName = $safeFilename . '-' . uniqid() . '.' . $file->guessExtension();

        try {
            $file->move($this->signatureUploadDirectory, $fileName);
        } catch (\Exception $e) {
            throw new \Exception('Error al guardar el archivo: ' . $e->getMessage());
        }

        return $fileName;
    }
}
