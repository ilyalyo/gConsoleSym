<?php

namespace AppBundle\Controller;

use AppBundle\Entity\Client;
use AppBundle\Entity\Website;
use AppBundle\Utils\GoogleUtils;
use Doctrine\ORM\EntityRepository;
use FOS\UserBundle\Util\UserManipulator;
use Google_Client;
use Google_Service_Oauth2;
use Google_Service_Webmasters;
use Nette\Utils\DateTime;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sg\DatatablesBundle\Datatable\Data\DatatableQuery;
use Swift_Message;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use AppBundle\Entity\User;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class DefaultController extends Controller
{
    /**
     * @Route("/", name="homepage")
     */
    public function indexAction(Request $request)
    {
        return $this->render('default/index.html.twig');
    }

    /**
     * @Route("/google_redirect", name="google_redirect")
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function gRedirectAction(Request $request)
    {
        $code = $request->get('code');
        $em = $this->getDoctrine()->getManager();
        $redirect_uri = $this->generateUrl('google_redirect', [], UrlGeneratorInterface::ABSOLUTE_URL);
        $googleClient = GoogleUtils::getGoogleClient($redirect_uri);      
        
        $token = $googleClient->fetchAccessTokenWithAuthCode($code);
        $refresh_token = $googleClient->fetchAccessTokenWithRefreshToken();
        $oauthService = new Google_Service_Oauth2($googleClient);

        $gId = $oauthService->userinfo->get()->getId();
        $gEmail = $oauthService->userinfo->get()->getEmail();
        $gPicture = $oauthService->userinfo->get()->getPicture();

        $client = $em->getRepository('AppBundle:Client')->findOneBy(['googleId' => $gId]);
        if($client == null){
            $client = new Client();
            $client->setGoogleId($gId);
            $client->setEmail($gEmail);
            $client->setPicture($gPicture);
            $client->setUser($this->getUser());
        }
        else{
            $client->setEmail($gEmail);
            $client->setPicture($gPicture);
        }

        $client->setToken($token);
        $client->setRefreshToken($refresh_token);
        $em->persist($client);
        $em->flush();
        GoogleUtils::updateData($googleClient, $em, $client);

        return new RedirectResponse($this->generateUrl('main'));
    }

    /**
     * @Route("/check_mail", name="check_mail")
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function mailAction(Request $request)
    {
        $message = Swift_Message::newInstance()
            ->setSubject('New registration')
            ->setFrom($this->getParameter('mailer_receiver'))
            ->setTo($this->getParameter('mailer_receiver'))
            ->setBody(
                "New user registered.",
                'text/html'
            );
        $this->get('mailer')->send($message);
        return new Response($message);
    }
}
