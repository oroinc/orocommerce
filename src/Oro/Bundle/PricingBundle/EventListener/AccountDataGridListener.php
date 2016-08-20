<?php

namespace Oro\Bundle\PricingBundle\EventListener;

use Oro\Bundle\PricingBundle\Entity\PriceListToAccount;
use Oro\Bundle\PricingBundle\Entity\BasePriceListRelation;

class AccountDataGridListener extends AbstractPriceListRelationDataGridListener
{
    const RELATION_CLASS_NAME = 'OroPricingBundle:PriceListToAccount';

    /**
     * {@inheritdoc}
     */
    protected function getRelations(array $priceListHolderIds)
    {
        return $this->registry->getManagerForClass('OroPricingBundle:PriceListToAccount')
            ->getRepository('OroPricingBundle:PriceListToAccount')
            ->getRelationsByHolders($priceListHolderIds);
    }

    /**
     * {@inheritdoc}
     * @param PriceListToAccount $relation
     */
    protected function getObjectId(BasePriceListRelation $relation)
    {
        return $relation->getAccount()->getId();
    }

    /**
     * {@inheritdoc}
     */
    protected function getRelationClassName()
    {
        return self::RELATION_CLASS_NAME;
    }
}
