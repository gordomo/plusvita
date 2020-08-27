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
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
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
    public function blanquear(Request $request, UserRepository $userRepository, ClienteRepository $pacienteRepository, DoctorRepository $staffRepository, MailerInterface $mailer, MailCodeRepository $mailCodeRepository): Response
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

                $email = (new Email())
                    ->from('morimartin@gmail.com')
                    ->to($userEmail)
                    //->cc('cc@example.com')
                    //->bcc('bcc@example.com')
                    //->replyTo('fabien@example.com')
                    //->priority(Email::PRIORITY_HIGH)
                    ->subject('Resetear Password del Sistema Plus Vita')
                    //->text('Resetear Password del Sistema Plus Vita');
                    ->html('<p>Use el siguiente codigo para resetear su password</p><p><h1>' . $code . '</h1></p>');

                try {
                    $mailer->send($email);
                } catch (TransportExceptionInterface $e) {
                    $error = true;
                    $mensaje = $e->getMessage();
                }
            }
        }

        if($error) {
            return $this->redirectToRoute('app_login',
                [
                    'last_username' => $userEmail,
                    'cambioDePassOk' => true,
                    'error' => '',
                    'customError' => 'Hubo un error enviado el correo con el codigo para recuperar la contraseña, por favor, intente de nuevo más tarde',
                ]);
        } else {
            return $this->render('resetPass/codigoEnviado.html.twig', [

                'userEmail' => $userEmail,
            ]);
        }

    }

}