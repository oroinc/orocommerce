<?php

namespace Oro\Bundle\ShippingBundle\Entity\Repository;

use Doctrine\ORM\EntityRepository;

class ShippingMethodTypeConfigRepository extends EntityRepository
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

    /**
     * @param array $ids
     */
    public function deleteByIds(array $ids)
    {
        $qb = $this->createQueryBuilder('methodTypeConfig');
        $qb->delete()
            ->where($qb->expr()->in('methodTypeConfig.id', ':ids'))
            ->setParameter('ids', $ids)
            ->getQuery()->execute();
    }
}
