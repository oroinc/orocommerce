<?php

namespace Oro\Bundle\PricingBundle\EventListener;

use Oro\Bundle\CustomerBundle\Entity\Customer;
use Oro\Bundle\PricingBundle\Entity\BasePriceListRelation;
use Oro\Bundle\PricingBundle\Entity\PriceListToCustomer;
use Oro\Bundle\WebsiteBundle\Entity\Website;

/**
 * Load and Save price list to customer relation when flat pricing storage enabled.
 */
class CustomerFlatPricingRelationFormListener extends AbstractFlatPricingRelationFormListener
{
    /**
     * @param Website $website
     * @param Customer $targetEntity
     * @return PriceListToCustomer|null
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    protected function getPriceListRelation(Website $website, $targetEntity): ?BasePriceListRelation
    {
        return $this->doctrineHelper
            ->getEntityRepository(PriceListToCustomer::class)
            ->getFirstRelation($website, $targetEntity);
    }

    /**
     * @param Website $website
     * @param Customer $targetEntity
     * @return PriceListToCustomer
     */
    protected function createNewRelation(Website $website, $targetEntity): BasePriceListRelation
    {
        $priceListRelation = new PriceListToCustomer();
        $priceListRelation->setWebsite($website);
        $priceListRelation->setCustomer($targetEntity);
        $priceListRelation->setSortOrder(0);

        return $priceListRelation;
    }

    /**
     * {@inheritdoc}
     */
    protected function handlePriceListChanges(Website $website, $targetEntity)
    {
        $this->triggerHandler->handleCustomerChange($targetEntity, $website);
    }
}
