<?php

namespace OroB2B\Bundle\PricingBundle\EventListener;

use OroB2B\Bundle\PricingBundle\Entity\BasePriceListRelation;
use OroB2B\Bundle\PricingBundle\Entity\PriceListToAccountGroup;

class AccountGroupDataGridListener extends AbstractPriceListRelationDataGridListener
{
    const RELATION_CLASS_NAME = 'OroB2BPricingBundle:PriceListToAccountGroup';
    const ENTITY_ALIAS = 'account_group';

    /**
     * {@inheritdoc}
     */
    protected function getRelations(array $priceListHolderIds)
    {
        return $this->registry->getManagerForClass('OroB2BPricingBundle:PriceListToAccountGroup')
            ->getRepository('OroB2BPricingBundle:PriceListToAccountGroup')
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

    /**
     * {@inheritdoc}
     */
    protected function getEntityAlias()
    {
        return self::ENTITY_ALIAS;
    }
}
