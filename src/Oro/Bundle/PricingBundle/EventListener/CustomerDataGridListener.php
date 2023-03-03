<?php

namespace Oro\Bundle\PricingBundle\EventListener;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\PricingBundle\Entity\BasePriceListRelation;
use Oro\Bundle\PricingBundle\Entity\PriceListToCustomer;

/**
 * Adds the following to customer grid:
 * * price list column and filter.
 * * price list data to selected records.
 */
class CustomerDataGridListener extends AbstractPriceListRelationDataGridListener
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
        return $this->doctrine->getRepository(PriceListToCustomer::class)
            ->getRelationsByHolders($priceListHolderIds);
    }

    /**
     * {@inheritDoc}
     */
    protected function getObjectId(BasePriceListRelation $relation): int
    {
        /** @var PriceListToCustomer $relation */
        return $relation->getCustomer()->getId();
    }

    /**
     * {@inheritDoc}
     */
    protected function getRelationClassName(): string
    {
        return PriceListToCustomer::class;
    }
}
