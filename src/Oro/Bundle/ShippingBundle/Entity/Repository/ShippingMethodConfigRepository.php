<?php

namespace Oro\Bundle\ShippingBundle\Entity\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Oro\Bundle\ShippingBundle\Entity\ShippingMethodConfig;

/**
 * Doctrine repository for ShippingMethodConfig entity
 */
class ShippingMethodConfigRepository extends ServiceEntityRepository
{
    /**
     * @param string $method
     */
    public function deleteByMethod($method)
    {
        $qb = $this->createQueryBuilder('methodConfig');

        $qb->delete()
            ->where(
                $qb->expr()->eq('methodConfig.method', ':method')
            )
            ->setParameter('method', $method);

        $qb->getQuery()->execute();
    }

    /**
     * @return array
     */
    public function findIdsWithoutTypeConfigs()
    {
        $qb = $this->createQueryBuilder('methodConfig');
        $qb->select('methodConfig.id')
            ->leftJoin('methodConfig.typeConfigs', 'typeConfig')
            ->where($qb->expr()->isNull('typeConfig.id'));

        return array_column($qb->getQuery()->execute(), 'id');
    }

    public function deleteByIds(array $ids)
    {
        $qb = $this->createQueryBuilder('methodConfig');
        $qb->delete()
            ->where($qb->expr()->in('methodConfig.id', ':ids'))
            ->setParameter('ids', $ids)
            ->getQuery()->execute();
    }

    /**
     * @param string|string[] $method
     *
     * @return ShippingMethodConfig[]
     */
    public function findByMethod($method)
    {
        return $this->findBy([
            'method' => $method
        ]);
    }

    public function configExistsByMethods(array $methods = []): bool
    {
        $qb = $this->createQueryBuilder('c');
        $qb
            ->select('COUNT(c.id)')
            ->where('c.method IN (:methods)')
            ->setParameter('methods', $methods);

        return (bool) $qb->getQuery()->getSingleScalarResult();
    }
}
