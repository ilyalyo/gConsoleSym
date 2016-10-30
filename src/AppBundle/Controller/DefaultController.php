<?php

namespace AppBundle\Controller;

use AppBundle\Entity\Client;
use AppBundle\Utils\GoogleUtils;
use Google_Client;
use Google_Service_Oauth2;
use Google_Service_Webmasters;
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
        // replace this example code with whatever you need
        return $this->render('default/index.html.twig');
    }

    /**
     * @Route("/main", name="main")
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function mainAction(Request $request)
    {
        $redirect_uri = $this->generateUrl('google_redirect', [], UrlGeneratorInterface::ABSOLUTE_URL);
        $client = GoogleUtils::getGoogleClient($redirect_uri);

        $authUrl = $client->createAuthUrl();

        return $this->render('default/main.html.twig',[
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
        $em = $this->getDoctrine()->getEntityManager();
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
            $client->setToken($token);
            $client->setPicture($gPicture);
            $client->setUser($this->getUser());
            $em->persist($client);
            $em->flush();
        }

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
        $repository = $this->getDoctrine()
            ->getRepository('AppBundle:User');
        $qb = $repository->createQueryBuilder ('u')
            ->where('u.roles != :roles')
            ->setParameter('roles', 'a:1:{i:0;s:10:"ROLE_ADMIN";}');

        $application = new Mesour\UI\Application;
        $application->setRequest($_REQUEST);
        $application->run();

        $source = new Mesour\DataGrid\Sources\DoctrineGridSource(User::class , 'id', $qb);

        $grid = new Mesour\UI\DataGrid('basicDataGrid', $application);
        $grid->setSource($source);
        $grid->enablePager(10);
        $selection = $grid->enableRowSelection();
        $links = $selection->getLinks();
        $links->addLink('Enable')
            ->setAjax(false)
            ->onCall[] = function () {

            $userManager = $this->get('fos_user.user_manager');
            $repository = $this->getDoctrine()
                ->getRepository('AppBundle:User');
            $ids = func_get_args()[0];
            foreach ($ids as $id => $isChecked){
                if($isChecked == "true") {
                    $user = $repository->find($id);
                    if($user != null){
                        $user->addRole(User::ROLE_APPROVED);
                        $userManager->updateUser($user);
                    }
                }
            }
            $url = $this->generateUrl('admin');
            return new RedirectResponse($url);
        };
        $links->addLink('Delete')
      //      ->setConfirm('Really delete all selected users?')
            ->onCall[] = function () {
            $userManager = $this->get('fos_user.user_manager');
            $repository = $this->getDoctrine()
                ->getRepository('AppBundle:User');
            $ids = func_get_args()[0];

            foreach ($ids as $id => $isChecked){
                if($isChecked == "true") {
                    $user = $repository->find($id);
                    $userManager->deleteUser($user);
                }
            }
            return new JsonResponse(array('id' => 15));
        };
        $grid->addText('id', '#');
        $grid->addText('username', 'Username');
        $grid->addText('email', 'Email');
        $grid->addText('hasRole', 'Enabled');

        // replace this example code with whatever you need
        return $this->render('default/admin.html.twig', [
            'grid' => $grid->create()
        ]);
    }
}
