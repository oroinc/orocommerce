<?php

namespace Oro\Bundle\PricingBundle\Duplicator;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\EntityBundle\ORM\InsertFromSelectQueryExecutor;
use Oro\Bundle\PricingBundle\Entity\PriceList;
use Oro\Bundle\PricingBundle\Entity\Repository\PriceListToProductRepository;

/**
 * Duplicates price list to product relationships from one price list to another.
 *
 * Copies all product assignments from a source price list to a target price list,
 * preserving the relationships and enabling efficient price list duplication.
 */
class PriceListToProductDuplicator
{
    /**
     * @var ManagerRegistry
     */
    protected $registry;

    /**
     * @var InsertFromSelectQueryExecutor
     */
    protected $insertQueryExecutor;

    /**
     * @var string
     */
    protected $entityName;

    public function __construct(
        ManagerRegistry $registry,
        InsertFromSelectQueryExecutor $insertQueryExecutor
    ) {
        $this->registry = $registry;
        $this->insertQueryExecutor = $insertQueryExecutor;
    }

    public function duplicate(PriceList $sourcePriceList, PriceList $targetPriceList)
    {
        /** @var PriceListToProductRepository $repository */
        $repository = $this->registry->getManagerForClass($this->entityName)
            ->getRepository($this->entityName);

        $repository->copyRelations($sourcePriceList, $targetPriceList, $this->insertQueryExecutor);
    }

    /**
     * @param string $entityName
     */
    public function setEntityName($entityName)
    {
        $this->entityName = $entityName;
    }
}
