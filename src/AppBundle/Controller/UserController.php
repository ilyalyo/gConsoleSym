<?php

namespace AppBundle\Controller;

use AppBundle\Entity\User;
use AppBundle\Entity\Website;
use AppBundle\Utils\GoogleUtils;
use Doctrine\ORM\EntityRepository;
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
        $em = $this->getDoctrine()->getManager();
        $clients = $em->getRepository('AppBundle:Client')->findBy(['user' => $this->getUser()]);

        $client = $em->getRepository('AppBundle:Client')->find($client_id);
        if($client == null && count($clients) > 0 )
            $client = $clients[0];

        $grid = null;
        if($client != null) {
            $form = $this->createFormBuilder()
                ->add('websites', EntityType::class,
                    array(
                        'class' => Website::class,
                        'label' => 'Website',
                        'query_builder' => function(EntityRepository $repository) use ($client) {
                            return $repository->createQueryBuilder('w')
                                ->where('w.client = :client')
                                ->setParameter('client',  $client);

                        },
                    ))
                ->getForm();

            $form->handleRequest($request);
            if ($form->isSubmitted())
                $website = $form['websites']->getData();
            else
                $website = $em->getRepository('AppBundle:Website')->findOneBy(['client' => $client]);

            if(isset($website)){
                $datatable = $this->get('app.datatable.record');
                $datatable->buildDatatable(['website_id' => $website->getId()]);
            }
        }

        $redirect_uri = $this->generateUrl('google_redirect', [], UrlGeneratorInterface::ABSOLUTE_URL);
        $googleClient = GoogleUtils::getGoogleClient($redirect_uri);

        $authUrl = $googleClient->createAuthUrl();

        return $this->render('default/main.html.twig',[
            'form' => isset($form) ? $form->createView() : null,
            'client' => $client,
            'datatable' => isset($datatable) ? $datatable : null,
            'clients' => $clients,
            'authUrl' => $authUrl,
        ]);
    }

    /**
     * @Route("/results/{id}", name="record_results", options = { "expose" = true })
     */
    public function indexResultsAction(Request $request, $id)
    {
        $datatable = $this->get('app.datatable.record');
        $datatable->buildDatatable(['website_id' => $id]);

        $query = $this->get('sg_datatables.query')->getQueryFrom($datatable);
        $query->getQuery()
            ->andWhere('record.website = :website')
            ->setParameter('website', $id);

        if(!empty($request->get('graph'))){
            $query->buildQuery();
            $query->getQuery()->resetDQLPart('select');
            $query->getQuery()->resetDQLPart('groupBy');
            $qb = $query->getQuery();
            $qb->addSelect('record.dateString');
            $qb->addSelect('SUM(record.clicks)');
            $qb->addSelect('SUM(record.impressions)');
            $qb->addSelect('SUM(record.ctr)');
            $qb->addSelect('SUM(record.position)');
            $qb->groupBy('record.dateString');
            $query->setQuery($qb);
            $result = $this->getDoctrine()->getEntityManager()->createQuery($qb->getQuery()->getDQL())
                ->setParameters($query->getQuery()->getParameters())->execute();
            $googleResult ["cols"] = [
                ["label" => "Date", "type" => "string"],
                ["label" => "Clicks", "type" => "number"],
                ["label" => "Impressions", "type" => "number"],
                ["label" => "Ctr", "type" => "number"],
                ["label" => "Position", "type" => "number"],
            ];
            foreach ($result as $r)
                $googleResult["rows"][] = ["c" => [
                    ["v" => $r["dateString"]->format('Y-m-d')],
                    ["v" => $r[1]],
                    ["v" => $r[2]],
                    ["v" => $r[3]],
                    ["v" => $r[4]],
                ]
                ];
            return new JsonResponse($googleResult);
        }

        return $query->getResponse();
    }

}