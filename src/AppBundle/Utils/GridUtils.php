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
        $grid->setDefaultOrder('date', 'DESC');

        $grid->addNumber('id', '#');
        $grid->addText('date_string', 'Date');
        $grid->addText('query', 'Query');
        $grid->addText('page', 'Page');
        $grid->addText('country', 'Country');
        $grid->addText('device', 'Device');

        $grid->addNumber('clicks', 'Clicks');
        $grid->addNumber('impressions', 'Impressions');
        $grid->addNumber('ctr', 'Ctr');
        $grid->addNumber('position', 'Position');
        return $grid->create();
    }
}