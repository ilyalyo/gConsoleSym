<?php
namespace AppBundle\Utils;


use AppBundle\Entity\Record;
use AppBundle\Entity\User;
use AppBundle\Entity\Website;
use Doctrine\ORM\EntityManager;
use Mesour\DataGrid\Sources\DoctrineGridSource;
use Mesour\UI\Application;
use Mesour\UI\DataGrid;

class GridUtils
{
    /**
     * @param EntityManager $em
     * @param Website $website
     * @return DataGrid
     */
    public static function configureGrid($em, $website){
        $application = new Application();
        $application->setRequest($_REQUEST);
        $application->run();

        $qb = $em->createQueryBuilder();
        $qb->select('r')
            ->from(Record::class, 'r')
            ->where('r.website = :website')
            ->setParameter('website', $website->getId());
        $source = new DoctrineGridSource(Record::class, 'id', $qb);
        $grid = new DataGrid('grid', $application);

        $grid->setSource($source);
        $grid->enableFilter(FALSE);
        $grid->enablePager(10);
        if(!empty($data)) {
            $grid->setDefaultOrder('date', 'DESC');

            $grid->addText('date', 'Date');
            $grid->addText('query', 'query');
            $grid->addText('page', 'Page');
            $grid->addText('country', 'country');
            $grid->addText('device', 'device');

            $grid->addNumber('clicks', 'Clicks');
            $grid->addNumber('impressions', 'impressions');
            $grid->addNumber('ctr', 'ctr');
            $grid->addNumber('position', 'position');
        }
        return $grid->create();
    }
}