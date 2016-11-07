<?php
namespace AppBundle\Command;

use AppBundle\Utils\GoogleUtils;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Doctrine\ORM\EntityManager;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class UpdateCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this->setName('app:update') ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $clients = $em->getRepository('AppBundle:Client')->findAll();
        foreach ($clients as $client){
            $redirect_uri = $this->getContainer()->get('router')->generate('google_redirect');
            $gClient = GoogleUtils::getGoogleClient($redirect_uri);
            $gClient->setAccessToken($client->getToken());
            GoogleUtils::updateData($gClient, $em, $client);
        }
    }
}