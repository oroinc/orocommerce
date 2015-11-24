<?php

namespace OroB2B\Bundle\PricingBundle\Entity\Repository;

use OroB2B\Bundle\PricingBundle\Entity\BasePriceListRelation;
use OroB2B\Bundle\WebsiteBundle\Entity\Website;

interface PriceListRepositoryInterface
{
    /**
     * @param object $entity
     * @param Website $website
     * @return BasePriceListRelation[]
     */
    public function getPriceLists($entity, Website $website);
}
