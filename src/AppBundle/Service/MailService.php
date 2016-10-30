<?php
/**
 * Created by PhpStorm.
 * User: ilia
 * Date: 30.10.16
 * Time: 23:20
 */

namespace AppBundle\Service;

use AppBundle\Entity\User;
use Swift_Mailer;
use Swift_Message;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorage;

class MailService
{
    private $mailer;
    private $mailer_no_reply;
    private $mailer_receiver;
    
    /**
     * RegistrationListener constructor.
     * @param Swift_Mailer $mailer
     * @param string $mailer_no_reply
     * @param string $mailer_receiver
     */
    public function __construct(Swift_Mailer $mailer, $mailer_no_reply, $mailer_receiver)
    {
        $this->mailer = $mailer;
        $this->mailer_no_reply = $mailer_no_reply;
        $this->mailer_receiver = $mailer_receiver;
    }

    public function onUserApproved($email)
    {
        $message = Swift_Message::newInstance()
            ->setSubject('Status changed')
            ->setFrom($this->mailer_no_reply)
            ->setTo($email)
            ->setBody(
                "You've been approved by admin!",
                'text/html'
            );
        $this->mailer->send($message);
    }

    public function onRegistrationSuccess()
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
    }
}