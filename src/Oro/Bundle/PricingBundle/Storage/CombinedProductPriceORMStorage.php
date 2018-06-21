<?php

namespace Oro\Bundle\PricingBundle\Storage;

use Doctrine\Common\Persistence\ManagerRegistry;
use Oro\Bundle\PricingBundle\Entity\CombinedPriceList;
use Oro\Bundle\PricingBundle\Entity\CombinedProductPrice;
use Oro\Bundle\PricingBundle\Entity\Repository\CombinedProductPriceRepository;
use Oro\Bundle\PricingBundle\Model\PriceListTreeHandler;
use Oro\Bundle\PricingBundle\Model\ProductPriceScopeCriteriaInterface;
use Oro\Bundle\PricingBundle\Sharding\ShardManager;

class CombinedProductPriceORMStorage implements ProductPriceStorageInterface
{
    /**
     * @var ShardManager
     */
    protected $shardManager;

    /**
     * @var ManagerRegistry
     */
    protected $registry;

    /**
     * @var PriceListTreeHandler
     */
    protected $priceListTreeHandler;

    /**
     * @param ManagerRegistry $registry
     * @param ShardManager $shardManager
     * @param PriceListTreeHandler $priceListTreeHandler
     */
    public function __construct(
        ManagerRegistry $registry,
        ShardManager $shardManager,
        PriceListTreeHandler $priceListTreeHandler
    ) {
        $this->registry = $registry;
        $this->shardManager = $shardManager;
        $this->priceListTreeHandler = $priceListTreeHandler;
    }

    /**
     * {@inheritdoc}
     */
    public function getPrices(
        ProductPriceScopeCriteriaInterface $scopeCriteria,
        array $productIds,
        array $productUnitCodes = null,
        array $currencies = null
    ) {
        $priceList = $this->getPriceListByScopeCriteria($scopeCriteria);
        if (!$priceList) {
            return [];
        }

        return $this->getRepository()->getPricesBatch(
            $this->shardManager,
            $priceList->getId(),
            $productIds,
            $productUnitCodes,
            $currencies
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getSupportedCurrencies(ProductPriceScopeCriteriaInterface $scopeCriteria)
    {
        $priceList = $this->getPriceListByScopeCriteria($scopeCriteria);
        if (!$priceList) {
            return [];
        }

        return $priceList->getCurrencies();
    }

    /**
     * @return CombinedProductPriceRepository
     */
    protected function getRepository()
    {
        return $this->registry
            ->getManagerForClass(CombinedProductPrice::class)
            ->getRepository(CombinedProductPrice::class);
    }

    /**
     * @param ProductPriceScopeCriteriaInterface $scopeCriteria
     * @return null|CombinedPriceList
     */
    protected function getPriceListByScopeCriteria(ProductPriceScopeCriteriaInterface $scopeCriteria)
    {
        return $this->priceListTreeHandler->getPriceList($scopeCriteria->getCustomer(), $scopeCriteria->getWebsite());
    }
}
