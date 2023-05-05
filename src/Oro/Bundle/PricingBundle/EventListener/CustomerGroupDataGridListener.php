<?php

namespace Oro\Bundle\PricingBundle\EventListener;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\PricingBundle\Entity\BasePriceListRelation;
use Oro\Bundle\PricingBundle\Entity\PriceListToCustomerGroup;

/**
 * Adds the following to customer group grid:
 * * price list column and filter.
 * * price list data to selected records.
 */
class CustomerGroupDataGridListener extends AbstractPriceListRelationDataGridListener
{
    private ManagerRegistry $doctrine;

    public function __construct(ManagerRegistry $doctrine)
    {
        $this->doctrine = $doctrine;
    }

    /**
     * {@inheritDoc}
     */
    protected function getRelations(array $priceListHolderIds): array
    {
        return $this->doctrine->getRepository(PriceListToCustomerGroup::class)
            ->getRelationsByHolders($priceListHolderIds);
    }

    /**
     * {@inheritDoc}
     */
    protected function getObjectId(BasePriceListRelation $relation): int
    {
        /** @var PriceListToCustomerGroup $relation */
        return $relation->getCustomerGroup()->getId();
    }

    /**
     * {@inheritDoc}
     */
    protected function getRelationClassName(): string
    {
        return PriceListToCustomerGroup::class;
    }
}
