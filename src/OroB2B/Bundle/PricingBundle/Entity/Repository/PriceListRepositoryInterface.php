<?php

namespace Oro\Bundle\PricingBundle\Entity\Repository;

use Oro\Bundle\PricingBundle\Entity\BasePriceListRelation;
use Oro\Bundle\WebsiteBundle\Entity\Website;

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
