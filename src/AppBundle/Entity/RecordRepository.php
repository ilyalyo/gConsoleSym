<?php

namespace AppBundle\Entity;

/**
 * DataRepository
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 */
class RecordRepository extends \Doctrine\ORM\EntityRepository
{
    /**
     * @param Website $website
     * @return string
     */
    public function getLastRecordDateAsString($website){

        return $this->getEntityManager()
            ->createQuery(
                'SELECT MAX(`date`) 
                FROM AppBundle:Record r 
                WHERE website_id = :website_id'
            )
            ->setParameter('website_id', $website->getId())
            ->getResult();
    }
}
