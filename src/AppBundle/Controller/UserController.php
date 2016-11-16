<?php

namespace AppBundle\Controller;

use AppBundle\Entity\Record;
use AppBundle\Entity\User;
use AppBundle\Entity\Website;
use AppBundle\Utils\GoogleUtils;
use DateTime;
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
     * @Route("/main/{client_id}/{website_id}", name="main")
     * @param Request $request
     * @param int $client_id
     * @param int $website_id
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function mainAction(Request $request, $client_id = -1, $website_id = -1)
    {
        $createdGrid = null;
        $graphData = null;
        $websites = null;

        $em = $this->getDoctrine()->getManager();
        $clients = $em->getRepository('AppBundle:Client')->findBy(['user' => $this->getUser()]);
        $client = $em->getRepository('AppBundle:Client')->find($client_id);
        if ($client == null && count($clients) > 0)
            $client = $clients[0];

        if ($client != null) {
            $websites = $em->getRepository('AppBundle:Website')->findBy(['client' => $client]);

            $website = $em->getRepository('AppBundle:Website')->find($website_id);
            if ($website == null && count($websites) > 0)
                $website = $websites[0];


            if (isset($website)) {
                $application = new Application();
                $application->setRequest($_REQUEST);
                $application->run();

                /** @var QueryBuilder $qb */
                $qb = $em->createQueryBuilder();
                $qb->select('r')
                    ->from(Record::class, 'r')
                    ->where('r.website = :website')
                    ->setParameter(':website', $website->getId());

                $data = $qb->getQuery()->getArrayResult();
                foreach ($data as $key => $d) {
                    $data[$key]['dateString'] = $d['dateString']->getTimestamp();
                    $data[$key]['roundedDate'] = $d['dateString']->format('Y-m-d');
                }

                $source = new \Mesour\DataGrid\Sources\ArrayGridSource('users', 'id', $data);

                $grid = new DataGrid('grid', $application);
                $grid->setSource($source);
                $grid->enableFilter(FALSE);

                $grid->enablePager(10);
                //$grid->setDefaultOrder('dateString', 'DESC');

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

                if($request->isMethod('GET')) {
                    $rawData = $source->fetchForExport();
                    usort($rawData, function ($a, $b) {
                        return $a["dateString"] > $b["dateString"];
                    });
                    $graphData = [];
                    foreach ($rawData as $d) {
                        $key = $d['roundedDate'];
                        if (array_key_exists($key, $graphData)) {
                            $graphData[$key] = [
                                'clicks' => $graphData[$key]['clicks'] + $d['clicks'],
                                'impressions' => $graphData[$key]['impressions'] + $d['impressions'],
                                'ctr' => $graphData[$key]['ctr'] + $d['ctr'],
                                'position' => $graphData[$key]['position'] + $d['position'],
                                'count' => $graphData[$key]['count'] + 1
                            ];
                        } else
                            $graphData[$key] = [
                                'clicks' => $d['clicks'],
                                'impressions' => $d['impressions'],
                                'ctr' => $d['ctr'],
                                'position' => $d['position'],
                                'count' => 1
                            ];
                    }
                    foreach ($graphData as $key => $d) {
                        $graphData[$key]['ctr'] = $graphData[$key]['ctr'] / $graphData[$key]['count'];
                        $graphData[$key]['position'] = $graphData[$key]['position'] / $graphData[$key]['count'];
                    }
                }
            }
        }

        $redirect_uri = $this->generateUrl('google_redirect', [], UrlGeneratorInterface::ABSOLUTE_URL);
        $googleClient = GoogleUtils::getGoogleClient($redirect_uri);

        return $this->render('default/main.html.twig', [
            'websites' => $websites,
            'website' => $website,
            'client' => $client,
            'grid' => $createdGrid,
            'graphData' => $graphData,
            'clients' => $clients,
            'authUrl' => $googleClient->createAuthUrl(),
        ]);
    }
}