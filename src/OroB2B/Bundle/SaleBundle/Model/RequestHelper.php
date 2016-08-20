<?php

namespace Oro\Bundle\SaleBundle\Model;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\EntityRepository;

use Oro\Bundle\RFPBundle\Entity\Request;

class RequestHelper
{
    /** @var ManagerRegistry */
    protected $registry;

    /** @var string */
    protected $quoteClass;

    /** @var string */
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
     * @return array|Request[]
     */
    public function getRequestsWoQuote($days = 2)
    {
        $date = new \DateTime('now', new \DateTimeZone('UTC'));
        $date->modify(sprintf('-%d days', $days));

        $subQueryBuilder = $this->getEntityRepositoryForClass($this->quoteClass)
            ->createQueryBuilder('q')
            ->where('q.request = r.id');

        $queryBuilder = $this->getEntityRepositoryForClass($this->requestClass)->createQueryBuilder('r');

        return $queryBuilder->select('r')
            ->where(
                $queryBuilder->expr()->not(
                    $queryBuilder->expr()->exists(
                        $subQueryBuilder->getDQL()
                    )
                ),
                'r.createdAt < :date'
            )
            ->setParameter('date', $date)
            ->getQuery()
            ->getResult();
    }

    /**
     * @param string $entityClass
     * @return EntityRepository
     */
    public function getEntityRepositoryForClass($entityClass)
    {
        return $this->registry
            ->getManagerForClass($entityClass)
            ->getRepository($entityClass);
    }
}
