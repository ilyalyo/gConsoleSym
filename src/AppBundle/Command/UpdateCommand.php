<?php
namespace AppBundle\Command;

use AppBundle\Exception\GoogleUpdateException;
use AppBundle\Utils\GoogleUtils;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Doctrine\ORM\EntityManager;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class UpdateCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this->setName('app:update')
            ->addArgument('client_id', InputArgument::OPTIONAL, 'Optional client id');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $client_id = $input->getArgument('client_id');
        if($client_id != null)
            $clients = [$em->getRepository('AppBundle:Client')->find($client_id)];
        else
            $clients = $em->getRepository('AppBundle:Client')->findAll();
        $redirect_uri = $this->getContainer()->get('router')->generate('google_redirect', [], UrlGeneratorInterface::ABSOLUTE_URL);
        $gClient = GoogleUtils::getGoogleClient($redirect_uri);
        foreach ($clients as $client){
            $gClient->setAccessToken($client->getToken());
            try {
                GoogleUtils::updateData($gClient, $em, $client);
            }
            catch (GoogleUpdateException $e){
                $authUrl = $gClient->createAuthUrl();
                $mailer = $this->getContainer()->get('app.mail');
                $mailer->onUpdateDataException($client->getEmail(), 
                    $e->getMessage(),
                    $authUrl);
            }
        }
    }
}