<?php

namespace AppBundle\Controller;

use AppBundle\Entity\Client;
use AppBundle\Utils\GoogleUtils;
use AppBundle\Utils\GridUtils;
use FOS\UserBundle\Util\UserManipulator;
use Google_Client;
use Google_Service_Oauth2;
use Google_Service_Webmasters;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Swift_Message;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Mesour;
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
     * @Route("/main/{client_id}", name="main")
     * @param Request $request
     * @param int $client_id
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function mainAction(Request $request, $client_id = -1)
    {
        $em = $this->getDoctrine()->getManager();
        $clients = $em->getRepository('AppBundle:Client')->findBy(['user' => $this->getUser()]);

        $client = $em->getRepository('AppBundle:Client')->find($client_id);
        if($client == null && count($clients) > 0 )
            $client = $clients[0];

        $grid = null;
        if($client != null) {
            $website = $em->getRepository('AppBundle:Website')->findOneBy(['client' => $client]);
            $grid = GridUtils::configureGrid($em, $website);
        }
        
        $redirect_uri = $this->generateUrl('google_redirect', [], UrlGeneratorInterface::ABSOLUTE_URL);
        $googleClient = GoogleUtils::getGoogleClient($redirect_uri);

        $authUrl = $googleClient->createAuthUrl();

        return $this->render('default/main.html.twig',[
            'client' => $client,
            'grid' => $grid,
            'clients' => $clients,
            'authUrl' => $authUrl,
        ]);
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
        $oauthService = new Google_Service_Oauth2($googleClient);
        $gId = $oauthService->userinfo->get()->getId();

        $client = $em->getRepository('AppBundle:Client')->findOneBy(['googleId' => $gId]);
        if($client == null){
            $gEmail = $oauthService->userinfo->get()->getEmail();
            $gPicture = $oauthService->userinfo->get()->getPicture();

            $client = new Client();
            $client->setGoogleId($gId);
            $client->setEmail($gEmail);
            $client->setPicture($gPicture);
            $client->setUser($this->getUser());
        }

        $client->setToken($token);
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

    /**
     * @Route("/admin", name="admin")
     * @Security("has_role('ROLE_ADMIN')")
     */
    public function adminAction(Request $request)
    {
        $application = new Mesour\UI\Application;
        $application->setRequest($_REQUEST);
        $application->run();

        $grid = new Mesour\UI\DataGrid('basicDataGrid', $application);
        $grid->enablePager(10);

        $selection = $grid->enableRowSelection();
        $links = $selection->getLinks();
        $links->addLink('Enable')
            ->setAjax(false)
            ->onCall[] = function () {
            $this->approveUser(true, func_get_args()[0]);
        };
        $links->addLink('Disable')
            ->setAjax(false)
            ->onCall[] = function () {
            $this->approveUser(false, func_get_args()[0]);
        };

        $repository = $this->getDoctrine()
            ->getRepository('AppBundle:User');
        /**@var \Doctrine\ORM\QueryBuilder $qb*/
        $qb = $repository->createQueryBuilder ('u')
            ->where('u.roles != :roles')
            ->setParameter('roles', 'a:1:{i:0;s:10:"ROLE_ADMIN";}');

        $source = new Mesour\DataGrid\Sources\DoctrineGridSource(User::class , 'id', $qb);
        $grid->setSource($source);

        $grid->addText('id', '#');
        $grid->addText('username', 'Username');
        $grid->addText('email', 'Email');
        $grid->addText('hasRole', 'Enabled');
        $createdGrid = $grid->create();

        //need to refresh page otherwise
        if($request->get('m_do') != null)
            return new RedirectResponse($this->generateUrl('admin'));

        return $this->render('default/admin.html.twig', [
            'grid' => $createdGrid
        ]);
    }

    private function approveUser($isApproved, $ids){
        $userManager = $this->get('fos_user.user_manager');
        $repository = $this->getDoctrine()
            ->getRepository('AppBundle:User');
        foreach ($ids as $id => $isChecked){
            if($isChecked == "true") {
                $user = $repository->find($id);
                if($user != null){
                    if($isApproved){
                        $user->addRole(User::ROLE_APPROVED);
                        $this->container->get('app.mail')->onUserApproved($user->getEmail());
                    }
                    else
                        $user->removeRole(User::ROLE_APPROVED);

                    $userManager->updateUser($user);
                }
            }
        }
    }
}
