<?php

namespace OroB2B\Bundle\PricingBundle\EventListener;

use Oro\Bundle\DataGridBundle\Datagrid\DatagridInterface;
use OroB2B\Bundle\PricingBundle\Entity\BasePriceListRelation;
use OroB2B\Bundle\PricingBundle\Entity\PriceListToAccountGroup;

class AccountGroupDataGridListener extends AbstractPriceListRelationDataGridListener
{
    /**
     * {@inheritdoc}
     */
    protected function addPriceListCondition(DatagridInterface $grid)
    {
        throw new \Exception('Not implemented');
    }

    /**
     * {@inheritdoc}
     */
    protected function getRelations(array $priceListHolderIds)
    {
        return $this->registry->getManagerForClass('OroB2BPricingBundle:PriceListToAccount')
            ->getRepository('OroB2BPricingBundle:PriceListToAccount')
            ->getRelationsByAccounts($priceListHolderIds);
    }

    /**
     * {@inheritdoc}
     * @param PriceListToAccountGroup $relation
     */
    protected function getObjectId(BasePriceListRelation $relation)
    {
        return $relation->getAccountGroup()->getId();
    }
}
