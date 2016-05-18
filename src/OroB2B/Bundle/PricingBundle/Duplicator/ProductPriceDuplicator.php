<?php

namespace OroB2B\Bundle\PricingBundle\Duplicator;

use Doctrine\Common\Persistence\ManagerRegistry;

use Oro\Bundle\EntityBundle\ORM\InsertFromSelectQueryExecutor;

use OroB2B\Bundle\PricingBundle\Entity\BasePriceList;
use OroB2B\Bundle\PricingBundle\Entity\Repository\ProductPriceRepository;

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
     * @var InsertFromSelectQueryExecutor
     */
    protected $insertQueryExecutor;

    /**
     * @param ManagerRegistry $registry
     * @param InsertFromSelectQueryExecutor $insertQueryExecutor
     */
    public function __construct(
        ManagerRegistry $registry,
        InsertFromSelectQueryExecutor $insertQueryExecutor
    ) {
        $this->registry = $registry;
        $this->insertQueryExecutor = $insertQueryExecutor;
    }

    /**
     * @param BasePriceList $sourcePriceList
     * @param BasePriceList $targetPriceList
     */
    public function duplicate(BasePriceList $sourcePriceList, BasePriceList $targetPriceList)
    {
        if ($targetPriceList->getPrices()->isEmpty()) {
            $this->getRepository()->copyPrices($sourcePriceList, $targetPriceList, $this->insertQueryExecutor);
        }
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
