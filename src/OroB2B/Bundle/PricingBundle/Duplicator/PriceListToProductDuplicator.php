<?php

namespace OroB2B\Bundle\PricingBundle\Duplicator;

use Symfony\Bridge\Doctrine\RegistryInterface;
use Oro\Bundle\EntityBundle\ORM\InsertFromSelectQueryExecutor;
use OroB2B\Bundle\PricingBundle\Entity\PriceList;
use OroB2B\Bundle\PricingBundle\Entity\Repository\PriceListToProductRepository;

class PriceListToProductDuplicator
{
    /**
     * @var RegistryInterface
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

    /**
     * @param RegistryInterface $registry
     * @param InsertFromSelectQueryExecutor $insertQueryExecutor
     */
    public function __construct(
        RegistryInterface $registry,
        InsertFromSelectQueryExecutor $insertQueryExecutor
    ) {
        $this->registry = $registry;
        $this->insertQueryExecutor = $insertQueryExecutor;
    }

    /**
     * @param PriceList $sourcePriceList
     * @param PriceList $targetPriceList
     */
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
