<?php

namespace Oro\Bundle\ShippingBundle\Entity\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Oro\Bundle\ShippingBundle\Entity\ShippingMethodTypeConfig;

/**
 * Doctrine repository for ShippingMethodTypeConfig entity
 */
class ShippingMethodTypeConfigRepository extends ServiceEntityRepository
{
    /**
     * @param string $method
     * @param string $type
     * @return array
     */
    public function findIdsByMethodAndType($method, $type)
    {
        $qb = $this->createQueryBuilder('methodTypeConfig');

        $qb->select('methodTypeConfig.id')
            ->join('methodTypeConfig.methodConfig', 'methodConfig')
            ->where(
                $qb->expr()->andX(
                    $qb->expr()->eq('methodConfig.method', ':method'),
                    $qb->expr()->eq('methodTypeConfig.type', ':type')
                )
            )
            ->setParameter('method', $method)
            ->setParameter('type', $type);

        return array_column($qb->getQuery()->execute(), 'id');
    }

    public function deleteByIds(array $ids)
    {
        $qb = $this->createQueryBuilder('methodTypeConfig');
        $qb->delete()
            ->where($qb->expr()->in('methodTypeConfig.id', ':ids'))
            ->setParameter('ids', $ids)
            ->getQuery()->execute();
    }

    /**
     * @param string $method
     *
     * @return ShippingMethodTypeConfig[]
     */
    public function findEnabledByMethodIdentifier($method)
    {
        return $this->createQueryBuilder('methodTypeConfig')
            ->select('methodTypeConfig')
            ->innerJoin('methodTypeConfig.methodConfig', 'methodConfig')
            ->where('methodTypeConfig.enabled = true')
            ->andWhere('methodConfig.method = :method')
            ->setParameter('method', $method)
            ->getQuery()->execute();
    }
}
