<?php
namespace AppBundle\EventListener;

use FOS\UserBundle\Event\FormEvent;
use FOS\UserBundle\Event\UserEvent;
use FOS\UserBundle\FOSUserEvents;
use Swift_Mailer;
use Swift_Message;
use Symfony\Bundle\FrameworkBundle\Routing\Router;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class UserRoleListener implements EventSubscriberInterface
{

    private $router;
    private $mailer;
    private $mailer_email;
    private $mailer_receiver;

    /**
     * RegistrationListener constructor.
     * @param Swift_Mailer $mailer
     * @param string $mailer_email
     */
    public function __construct(Swift_Mailer $mailer, $mailer_email)
    {
        $this->mailer = $mailer;
        $this->mailer_email = $mailer_email;
    }

    public static function getSubscribedEvents()
    {
        return array(
            FOSUserEvents::USER_PROMOTED => 'onUserPromoted',
        );
    }

    public function onUserPromoted(UserEvent $event)
    {
        $user = $event->getUser();
        $message = Swift_Message::newInstance()
            ->setSubject('Status changed')
            ->setFrom($this->mailer_email)
            ->setTo($user->getEmail())
            ->setBody(
                "You've been approved by admin!",
                'text/html'
            );
        $this->mailer->send($message);
    }
}