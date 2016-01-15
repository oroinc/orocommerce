<?php

namespace OroB2B\Bundle\SaleBundle\Model;

use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\EntityManager;

use OroB2B\Bundle\RFPBundle\Entity\Request;
use OroB2B\Bundle\SaleBundle\Entity\Quote;

class RequestHelper
{
    /** @var ManagerRegistry */
    protected $registry;

    /**
     * @param ManagerRegistry $registry
     */
    public function __construct(ManagerRegistry $registry)
    {
        $this->registry = $registry;
    }

    /**
     * @param int $days
     * @return Collection|Request[]
     */
    public function getRequestsWoQuote($days = 2)
    {
        $date = new \DateTime();
        $date->modify(sprintf('-%d days', $days));
        /** @var EntityManager $manager */
        $manager = $this->registry->getManagerForClass('OroB2BRFPBundle:Request');
        $subQueryBuilder = $manager
            ->getRepository('OroB2BSaleBundle:Quote')
            ->createQueryBuilder('q');
        $subQueryBuilder->where('q.request = r.id');
        $queryBuilder = $manager
            ->getRepository('OroB2BRFPBundle:Request')
            ->createQueryBuilder('r');
        $queryBuilder
            ->select('r')
            ->where($queryBuilder->expr()->not($queryBuilder->expr()->exists($subQueryBuilder->getDql())))
            ->andWhere('r.createdAt < :date')
            ->setParameter('date', $date);
        ;

        return $queryBuilder->getQuery()->getResult();
    }
}
