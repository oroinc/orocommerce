<?php

namespace Oro\Bundle\ShippingBundle\Entity\Repository;

use Doctrine\ORM\EntityRepository;
use Oro\Bundle\ShippingBundle\Entity\ShippingMethodConfig;

class ShippingMethodTypeConfigRepository extends EntityRepository
{
    /**
     * @param ShippingMethodConfig $methodConfig
     * @param string $type
     */
    public function deleteByMethodAndType(ShippingMethodConfig $methodConfig, $type)
    {
        $qb = $this->createQueryBuilder('methodTypeConfig');

        $qb->delete()
            ->where(
                $qb->expr()->andX(
                    $qb->expr()->eq('methodTypeConfig.methodConfig', ':methodConfig'),
                    $qb->expr()->eq('methodTypeConfig.type', ':type')
                )
            )
            ->setParameter('methodConfig', $methodConfig)
            ->setParameter('type', $type);

        $qb->getQuery()->execute();
    }
}
