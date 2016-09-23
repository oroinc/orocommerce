<?php

namespace Oro\Bundle\PricingBundle\EventListener;

use Oro\Bundle\PricingBundle\Entity\BasePriceListRelation;
use Oro\Bundle\PricingBundle\Entity\PriceListToAccountGroup;

class AccountGroupDataGridListener extends AbstractPriceListRelationDataGridListener
{
    const RELATION_CLASS_NAME = 'OroPricingBundle:PriceListToAccountGroup';

    /**
     * {@inheritdoc}
     */
    protected function getRelations(array $priceListHolderIds)
    {
        return $this->registry->getManagerForClass('OroPricingBundle:PriceListToAccountGroup')
            ->getRepository('OroPricingBundle:PriceListToAccountGroup')
            ->getRelationsByHolders($priceListHolderIds);
    }

    /**
     * {@inheritdoc}
     * @param PriceListToAccountGroup $relation
     */
    protected function getObjectId(BasePriceListRelation $relation)
    {
        return $relation->getAccountGroup()->getId();
    }

    /**
     * {@inheritdoc}
     */
    protected function getRelationClassName()
    {
        return self::RELATION_CLASS_NAME;
    }
}
