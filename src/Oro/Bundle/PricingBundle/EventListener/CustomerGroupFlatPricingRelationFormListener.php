<?php

namespace Oro\Bundle\PricingBundle\EventListener;

use Oro\Bundle\CustomerBundle\Entity\CustomerGroup;
use Oro\Bundle\PricingBundle\Entity\BasePriceListRelation;
use Oro\Bundle\PricingBundle\Entity\PriceListToCustomerGroup;
use Oro\Bundle\WebsiteBundle\Entity\Website;

/**
 * Load and Save price list to customer group relation when flat pricing storage enabled.
 */
class CustomerGroupFlatPricingRelationFormListener extends AbstractFlatPricingRelationFormListener
{
    /**
     * @param Website $website
     * @param CustomerGroup $targetEntity
     * @return PriceListToCustomerGroup|null
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    protected function getPriceListRelation(Website $website, $targetEntity): ?BasePriceListRelation
    {
        return $this->doctrineHelper
            ->getEntityRepository(PriceListToCustomerGroup::class)
            ->getFirstRelation($website, $targetEntity);
    }

    /**
     * @param Website $website
     * @param CustomerGroup $targetEntity
     * @return PriceListToCustomerGroup
     */
    protected function createNewRelation(Website $website, $targetEntity): BasePriceListRelation
    {
        $priceListRelation = new PriceListToCustomerGroup();
        $priceListRelation->setWebsite($website);
        $priceListRelation->setCustomerGroup($targetEntity);
        $priceListRelation->setSortOrder(0);

        return $priceListRelation;
    }

    /**
     * {@inheritdoc}
     */
    protected function handlePriceListChanges(Website $website, $targetEntity)
    {
        $this->triggerHandler->handleCustomerGroupChange($targetEntity, $website);
    }
}
