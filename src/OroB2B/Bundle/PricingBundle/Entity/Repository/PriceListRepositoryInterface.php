<?php

namespace OroB2B\Bundle\PricingBundle\Entity\Repository;

use OroB2B\Bundle\PricingBundle\Entity\BasePriceListRelation;
use OroB2B\Bundle\WebsiteBundle\Entity\Website;

interface PriceListRepositoryInterface
{
    /**
     * @param object $entity
     * @param Website $website
     * @param string $order
     * @return BasePriceListRelation[]
     */
    public function getPriceLists($entity, Website $website, $order);
}
