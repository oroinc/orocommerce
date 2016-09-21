<?php

namespace Oro\Bundle\ShippingBundle\Entity\Repository;

use Doctrine\ORM\EntityRepository;

use Oro\Bundle\EntityBundle\ORM\InsertFromSelectQueryExecutor;
use Oro\Bundle\PricingBundle\Entity\BasePriceList;
use Oro\Bundle\PricingBundle\Entity\PriceList;
use Oro\Bundle\PricingBundle\Entity\ProductPrice;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Bundle\ShippingBundle\Entity\ShippingRuleMethodConfig;

class ShippingRuleMethodTypeConfigRepository extends EntityRepository
{
    /**
     * @param ShippingRuleMethodConfig $methodConfig
     * @param string $type
     */
    public function deleteByMethodAndType(ShippingRuleMethodConfig $methodConfig, $type)
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
