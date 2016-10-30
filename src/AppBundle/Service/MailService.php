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
    private $token_storage;
    private $mailer;
    private $mailer_email;
    private $mailer_no_reply;
    private $mailer_receiver;
    
    /**
     * RegistrationListener constructor.
     * @param TokenStorage $token_storage
     * @param Swift_Mailer $mailer
     * @param string $mailer_no_reply
     * @param string $mailer_receiver
     */
    public function __construct(TokenStorage $token_storage, Swift_Mailer $mailer, $mailer_no_reply, $mailer_receiver)
    {
        $this->token_storage = $token_storage;
        $this->mailer = $mailer;
        $this->mailer_no_reply = $mailer_no_reply;
        $this->mailer_receiver = $mailer_receiver;
    }

    public function onUserApproved()
    {
        $user = $this->token_storage->getToken()->getUser();
        
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