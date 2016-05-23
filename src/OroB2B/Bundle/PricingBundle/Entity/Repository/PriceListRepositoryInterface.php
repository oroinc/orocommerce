<?php

namespace OroB2B\Bundle\PricingBundle\Entity\Repository;

use OroB2B\Bundle\PricingBundle\Entity\BasePriceListRelation;
use OroB2B\Bundle\WebsiteBundle\Entity\Website;

interface PriceListRepositoryInterface
{
    /**
     * @param object $entity
     * @param Website $website
     * @param string $sortOrder
     * @return BasePriceListRelation[]
     */
    public function getPriceLists($entity, Website $website, $sortOrder);

    /**
     * @param array|int[] $holdersIds
     * @return BasePriceListRelation[]
     */
    public function getRelationsByHolders(array $holdersIds);
}
