<?php

namespace AppBundle\Controller;

use AppBundle\Entity\Record;
use AppBundle\Entity\User;
use AppBundle\Entity\Website;
use AppBundle\Utils\GoogleUtils;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\QueryBuilder;
use Mesour\DataGrid\Sources\DoctrineGridSource;
use Mesour\UI\Application;
use Mesour\UI\DataGrid;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * @Route("/u")
 */
class UserController extends Controller
{
    /**
     * @Route("/main/{client_id}", name="main")
     * @param Request $request
     * @param int $client_id
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function mainAction(Request $request, $client_id = -1)
    {
        $createdGrid = null;
        $graphData = null;

        $em = $this->getDoctrine()->getManager();
        $clients = $em->getRepository('AppBundle:Client')->findBy(['user' => $this->getUser()]);
        $client = $em->getRepository('AppBundle:Client')->find($client_id);
        if ($client == null && count($clients) > 0)
            $client = $clients[0];

        if ($client != null) {
            $form = $this->createFormBuilder()
                ->add('websites', EntityType::class,
                    array(
                        'class' => Website::class,
                        'label' => 'Website',
                        'query_builder' => function (EntityRepository $repository) use ($client) {
                            return $repository->createQueryBuilder('w')
                                ->where('w.client = :client')
                                ->setParameter('client', $client);
                        },
                    ))
                ->getForm();

            $form->handleRequest($request);
            if ($form->isSubmitted())
                $website = $form['websites']->getData();
            else
                $website = $em->getRepository('AppBundle:Website')->findOneBy(['client' => $client]);

            if (isset($website)) {
                $application = new Application();
                $application->setRequest($_REQUEST);
                $application->run();

                /** @var QueryBuilder $cqb */
                $cqb = $em->createQueryBuilder();
                $cqb->select('r')
                    ->from(Record::class, 'r')
                    ->andWhere('r.website = :website')
                    ->setParameter('website', $website->getId());
                $source = new DoctrineGridSource(Record::class, 'id', $cqb);
                $grid = new DataGrid('grid', $application);
                $grid->setSource($source);
                $grid->enableFilter(FALSE);

                $grid->enablePager(10);
                $grid->setDefaultOrder('dateString', 'DESC');

                $grid->addNumber('id', '#');
                $grid->addDate('dateString', 'Date');
                $grid->addText('query', 'Query');
                $grid->addText('page', 'Page');
                $grid->addText('country', 'Country');
                $grid->addText('device', 'Device');

                $grid->addNumber('clicks', 'Clicks');
                $grid->addNumber('impressions', 'Impressions');
                $grid->addNumber('ctr', 'Ctr');
                $grid->addNumber('position', 'Position');
                $createdGrid = $grid->create();

                $cqb = $source->cloneQueryBuilder();

               /* var_dump($cqb->getDQL());
                var_dump($cqb->getParameters());
                die();*/

                $cqb->resetDQLPart('groupBy');
                $cqb->select('r.dateString as date');
                $cqb->addSelect('SUM(r.clicks) as clicks');
                $cqb->addSelect('SUM(r.impressions) as impressions');
                $cqb->addSelect('AVG(r.ctr) as ctr');
                $cqb->addSelect('AVG(r.position) as position');
                $cqb->groupBy('r.dateString');
                $cqb->orderBy('date');
                //var_dump($qb->getQuery()->getParameters());die();
                $graphData = $this->getDoctrine()->getEntityManager()
                    ->createQuery($cqb->getQuery()->getDQL())
                    ->setParameters($source->getQuery()->getParameters())->execute();
            }
        }

        $redirect_uri = $this->generateUrl('google_redirect', [], UrlGeneratorInterface::ABSOLUTE_URL);
        $googleClient = GoogleUtils::getGoogleClient($redirect_uri);

        return $this->render('default/main.html.twig', [
            'form' => isset($form) ? $form->createView() : null,
            'client' => $client,
            'grid' => $createdGrid,
            'graphData' => $graphData,
            'clients' => $clients,
            'authUrl' => $googleClient->createAuthUrl(),
        ]);
    }
}