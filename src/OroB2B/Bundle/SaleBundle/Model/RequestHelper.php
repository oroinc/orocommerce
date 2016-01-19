<?php

namespace OroB2B\Bundle\SaleBundle\Model;

use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\EntityRepository;

use OroB2B\Bundle\RFPBundle\Entity\Request;

class RequestHelper
{
    /** @var ManagerRegistry */
    protected $registry;

    /**
     * @var string
     */
    protected $quoteClass;

    /**
     * @var string
     */
    protected $requestClass;

    /**
     * @param ManagerRegistry $registry
     * @param string $quoteClass
     * @param string $requestClass
     */
    public function __construct(ManagerRegistry $registry, $quoteClass, $requestClass)
    {
        $this->registry = $registry;
        $this->quoteClass = $quoteClass;
        $this->requestClass = $requestClass;
    }

    /**
     * @param int $days
     * @return Collection|Request[]
     */
    public function getRequestsWoQuote($days = 2)
    {
        $date = new \DateTime();
        $date->modify(sprintf('-%d days', $days));
        $subQueryBuilder = $this
            ->getEntityRepositoryForClass($this->quoteClass)
            ->createQueryBuilder('q');
        $subQueryBuilder->where('q.request = r.id');

        $queryBuilder = $this
            ->getEntityRepositoryForClass($this->requestClass)
            ->createQueryBuilder('r');

        $queryBuilder
            ->select('r')
            ->where($queryBuilder->expr()->not($queryBuilder->expr()->exists($subQueryBuilder->getDql())))
            ->andWhere('r.createdAt < :date')
            ->setParameter('date', $date);

        return $queryBuilder->getQuery()->getResult();
    }

    /**
     * @param $entityClass
     * @return EntityRepository
     */
    public function getEntityRepositoryForClass($entityClass)
    {
        return $this->registry
            ->getManagerForClass($entityClass)
            ->getRepository($entityClass);
    }
}
