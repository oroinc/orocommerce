<?php

namespace Oro\Bundle\PricingBundle\Duplicator;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\PricingBundle\Entity\BasePriceList;
use Oro\Bundle\PricingBundle\Entity\Repository\ProductPriceRepository;
use Oro\Bundle\PricingBundle\ORM\ShardQueryExecutorInterface;

/**
 * Duplicates product prices from one price list to another.
 *
 * Copies all product price entries from a source price list to a target price list,
 * enabling efficient duplication of pricing data across price lists.
 */
class ProductPriceDuplicator
{
    /**
     * @var ManagerRegistry
     */
    protected $registry;

    /**
     * @var ProductPriceRepository
     */
    protected $repository;

    /**
     * @var string
     */
    protected $priceListClass;

    /**
     * @var ShardQueryExecutorInterface
     */
    protected $insertQueryExecutor;

    public function __construct(
        ManagerRegistry $registry,
        ShardQueryExecutorInterface $insertQueryExecutor
    ) {
        $this->registry = $registry;
        $this->insertQueryExecutor = $insertQueryExecutor;
    }

    public function duplicate(BasePriceList $sourcePriceList, BasePriceList $targetPriceList)
    {
        $this->getRepository()->copyPrices($sourcePriceList, $targetPriceList, $this->insertQueryExecutor);
    }

    /**
     * @return ProductPriceRepository
     */
    protected function getRepository()
    {
        if (!$this->repository) {
            $this->repository = $this->registry->getManagerForClass($this->priceListClass)
                ->getRepository($this->priceListClass);
        }

        return $this->repository;
    }

    /**
     * @param string $priceListClass
     */
    public function setPriceListClass($priceListClass)
    {
        $this->priceListClass = $priceListClass;
    }
}
