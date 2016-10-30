<?php
namespace AppBundle\EventListener;

use AppBundle\Service\MailService;
use FOS\UserBundle\Event\FormEvent;
use FOS\UserBundle\FOSUserEvents;
use Swift_Mailer;
use Swift_Message;
use Symfony\Bundle\FrameworkBundle\Routing\Router;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class RegistrationListener implements EventSubscriberInterface
{

    private $mailer;
    private $router;
  
    /**
     * RegistrationListener constructor.
     * @param MailService $mailer
     * @param Router $router
     */
    public function __construct(MailService $mailer, Router $router)
    {
        $this->mailer = $mailer;
        $this->router = $router;
    }

    public static function getSubscribedEvents()
    {
        return array(
            FOSUserEvents::REGISTRATION_SUCCESS => 'onRegistrationSuccess',
        );
    }

    public function onRegistrationSuccess(FormEvent $event)
    {
        $this->mailer->onRegistrationSuccess();

        $url = $this->router->generate('main');
        $event->setResponse(new RedirectResponse($url));
    }
}