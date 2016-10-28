<?php

namespace AppBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Mesour;
use AppBundle\Entity\User;
class DefaultController extends Controller
{
    /**
     * @Route("/", name="homepage")
     */
    public function indexAction(Request $request)
    {
        // replace this example code with whatever you need
        return $this->render('default/index.html.twig', [
            'base_dir' => realpath($this->getParameter('kernel.root_dir').'/..').DIRECTORY_SEPARATOR,
        ]);
    }  
    
    /**
     * @Route("/main", name="main")
     */
    public function mainAction(Request $request)
    {
        // replace this example code with whatever you need
        return $this->render('default/main.html.twig', [
            'base_dir' => realpath($this->getParameter('kernel.root_dir').'/..').DIRECTORY_SEPARATOR,
        ]);
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
        $grid->enablePager(5);
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
                        $user->addRole(User::ROLE_INVITED);
                        $userManager->updateUser($user);
                    }
                }
            }
            return new JsonResponse(array('id' => 15));
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
