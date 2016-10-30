<?php
namespace AppBundle\EventListener;

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

    private $router;
    private $mailer;
    private $mailer_no_reply;
    private $mailer_receiver;

    /**
     * RegistrationListener constructor.
     * @param Router $router
     * @param Swift_Mailer $mailer
     * @param string $mailer_no_reply
     * @param string $mailer_receiver
     */
    public function __construct(Router $router, Swift_Mailer $mailer, $mailer_no_reply, $mailer_receiver)
    {
        $this->router = $router;
        $this->mailer = $mailer;
        $this->mailer_no_reply = $mailer_no_reply;
        $this->mailer_receiver = $mailer_receiver;
    }

    public static function getSubscribedEvents()
    {
        return array(
            FOSUserEvents::REGISTRATION_SUCCESS => 'onRegistrationSuccess',
        );
    }

    public function onRegistrationSuccess(FormEvent $event)
    {
           $message = Swift_Message::newInstance()
            ->setSubject('New registration')
            ->setFrom($this->mailer_no_reply)
            ->setTo($this->mailer_receiver)
            ->setBody(
                "New user registered.",
                'text/html'
            );
        $this->mailer->send($message);

        $url = $this->router->generate('main');
        $event->setResponse(new RedirectResponse($url));
    }
}