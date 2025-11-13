<?php

namespace App\Controller;

use App\Entity\MailCode;
use App\Repository\ClienteRepository;
use App\Repository\DoctorRepository;
use App\Repository\MailCodeRepository;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Service\ResetPasswordMailerService;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/restPass")
 */
class ResetPassController extends AbstractController
{
    /**
     * @Route("/", name="reset_password_index", methods={"GET"})
     */
    public function index(): Response
    {
        return $this->render('resetPass/index.html.twig', [


        ]);
    }

    /**
     * @Route("/nuevaClave", name="reset_password_code", methods={"POST"})
     */
    public function checkCode(Request $request, UserRepository $userRepository, ClienteRepository $pacienteRepository, DoctorRepository $staffRepository, MailerInterface $mailer, MailCodeRepository $mailCodeRepository): Response
    {

        $isCsrfTokenValid = $this->isCsrfTokenValid('authenticate', $request->request->get('_csrf_token'));
        $error = false;
        $customError = false;
        if ($isCsrfTokenValid) {
            $code = $request->get('code');
            $pass = $request->get('clave');
            global $kernel;
            if (method_exists($kernel, 'getKernel')) $kernel = $kernel->getKernel();



            $userMail = $request->get('email');
            //dd($code);
            //dd($userMail);
            $mailCode = $mailCodeRepository->findOneBy(['mail' => $userMail, 'code' => $code]);

            if ($mailCode) {
                switch ($mailCode->getType()) {
                    case 1:
                        $user = $userRepository->findOneBy(['email' => $userMail]);
                        $pass = $kernel->getContainer()->get('security.password_encoder')->encodePassword($user, $pass);
                        break;
                    case 2:
                        $user = $staffRepository->findOneBy(['email' => $userMail]);
                        break;
                    case 3:
                        //$user = $pacienteRepository->findOneBy(['email' => $userMail]);
                        break;
                }
                if($user) {

                    $user->setPassword($pass);
                    $entityManager = $this->getDoctrine()->getManager();
                    $entityManager->persist($user);
                    $entityManager->flush();
                } else {
                    throw new \LogicException('No existe un usuario con ese email');
                }


            }
        }

        return $this->redirectToRoute('app_login',
            [
                'cambioDePassOk' => true,
            ]);
    }

    /**
     * @Route("/blanquear", name="reset_password_blanquear", methods={"POST"})
     */
    public function blanquear(Request $request, UserRepository $userRepository, ClienteRepository $pacienteRepository, DoctorRepository $staffRepository, MailCodeRepository $mailCodeRepository, ResetPasswordMailerService $mailerService): Response
    {

        $mensaje = '';

        $isCsrfTokenValid = $this->isCsrfTokenValid('authenticate', $request->request->get('_csrf_token'));
        $error = false;
        if($isCsrfTokenValid) {
            $userEmail = $request->get('email');

            $user = $userRepository->findOneBy(['email' => $userEmail]);
            $staff = $staffRepository->findOneBy(['email' => $userEmail]);
            //$paciente =$pacienteRepository->findOneBy(['email' => $userEmail]);

            if (
                   $user
                || $staff
            //    || $paciente TODO habilitar pacientes
            ) {
                $chars = "abcdefghijkmnopqrstuvwxyz023456789";
                srand((double)microtime() * 1000000);
                $i = 0;
                $code = '';

                while ($i <= 7) {
                    $num = rand() % 33;
                    $tmp = substr($chars, $num, 1);
                    $code = $code . $tmp;
                    $i++;
                }

                $mailCode = $mailCodeRepository->findOneBy(['mail' => $userEmail]);
                if (!$mailCode) {
                    $mailCode = new MailCode();
                }
                $mailCode->setCode($code);
                $mailCode->setMail($userEmail);
                if($user) {
                    $mailCode->setType(1); //user
                } elseif ($staff) {
                    $mailCode->setType(2); //staff
                } else {
                    $mailCode->setType(3); //paciente
                }


                $entityManager = $this->getDoctrine()->getManager();
                $entityManager->persist($mailCode);
                $entityManager->flush();

                // Enviar email usando el servicio
                $result = $mailerService->sendResetPasswordEmail($userEmail, $code);
                
                if (!$result['success']) {
                    $error = true;
                    $mensaje = $result['error'] ?? $result['message'];
                }
            }
        }

        if($error) {
            // En desarrollo, mostrar el error completo para debugging
            $isDev = ($_ENV['APP_ENV'] ?? 'dev') === 'dev';
            $errorMessage = $isDev ? $mensaje : 'Error al enviar el correo. Por favor, intente nuevamente más tarde.';
            
            return $this->redirectToRoute('app_login',
                [
                    'last_username' => $userEmail ?? '',
                    'cambioDePassOk' => false,
                    'error' => '',
                    'customError' => $errorMessage,
                ]);
        } else {
            return $this->render('resetPass/codigoEnviado.html.twig', [
                'userEmail' => $userEmail,
            ]);
        }

    }

    /**
     * Escribe un mensaje en un archivo de log en var/log/
     */
    private function writeToLogFile(string $filename, string $message): void
    {
        $logDir = $this->getParameter('kernel.project_dir') . '/var/log';
        $logFile = $logDir . '/' . $filename;
        
        // Asegurar que el directorio existe
        if (!is_dir($logDir)) {
            mkdir($logDir, 0777, true);
        }
        
        // Escribir al archivo (append mode)
        file_put_contents($logFile, $message, FILE_APPEND | LOCK_EX);
    }
}