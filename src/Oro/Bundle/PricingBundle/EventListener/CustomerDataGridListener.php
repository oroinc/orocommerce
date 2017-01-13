<?php

namespace Oro\Bundle\PricingBundle\EventListener;

use Oro\Bundle\PricingBundle\Entity\PriceListToCustomer;
use Oro\Bundle\PricingBundle\Entity\BasePriceListRelation;

class CustomerDataGridListener extends AbstractPriceListRelationDataGridListener
{
    const RELATION_CLASS_NAME = 'OroPricingBundle:PriceListToCustomer';

    /**
     * {@inheritdoc}
     */
    protected function getRelations(array $priceListHolderIds)
    {
        return $this->registry->getManagerForClass('OroPricingBundle:PriceListToCustomer')
            ->getRepository('OroPricingBundle:PriceListToCustomer')
            ->getRelationsByHolders($priceListHolderIds);
    }

    /**
     * {@inheritdoc}
     * @param PriceListToCustomer $relation
     */
    protected function getObjectId(BasePriceListRelation $relation)
    {
        return $relation->getCustomer()->getId();
    }

    /**
     * {@inheritdoc}
     */
    protected function getRelationClassName()
    {
        return self::RELATION_CLASS_NAME;
    }
}
