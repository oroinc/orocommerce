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

class ShippingRuleMethodConfigRepository extends EntityRepository
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
}
