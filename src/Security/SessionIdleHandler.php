<?php

namespace App\Security;

use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

class SessionIdleHandler
{

    protected $session;
    protected $securityToken;
    protected $router;
    protected $maxIdleTime;

    public function __construct($maxIdleTime, SessionInterface $session, TokenStorageInterface $securityToken, RouterInterface $router)
    {
        $this->session = $session;
        $this->securityToken = $securityToken;
        $this->router = $router;
        $this->maxIdleTime = $maxIdleTime;
    }

    public function onKernelRequest(RequestEvent $event)
    {
        if (HttpKernelInterface::MASTER_REQUEST != $event->getRequestType()) {

            return;
        }

        if ($this->maxIdleTime > 0) {
            $this->session->start();
            $lapse = time() - $this->session->getMetadataBag()->getLastUsed();

            if ($lapse > $this->maxIdleTime) {
                setcookie('timeout', true);
                return;

            } else {
                setcookie('timeout', null);
                unset($_COOKIE['timeout']);
            }

            if (isset($_COOKIE['keepLogin']) && $_COOKIE['keepLogin'] == 0) {
                $this->securityToken->setToken(null);
                $this->session->getFlashBag()->set('info', 'You have been logged out due to inactivity.');
                // logout is defined in security.yaml.  See 'Logging Out' section here:
                $event->setResponse(new RedirectResponse($this->router->generate('app_logout')));
                setcookie('keepLogin', null);
            }
        }
    }
}