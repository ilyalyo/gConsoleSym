<?php

namespace AppBundle\Controller;

use AppBundle\Entity\User;
use Mesour;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;

class AdminController extends Controller
{
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