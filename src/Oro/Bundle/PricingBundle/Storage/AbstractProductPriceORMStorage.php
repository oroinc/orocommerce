<?php

namespace Oro\Bundle\PricingBundle\Storage;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\PricingBundle\Entity\BasePriceList;
use Oro\Bundle\PricingBundle\Entity\Repository\BaseProductPriceRepository;
use Oro\Bundle\PricingBundle\Model\ProductPriceScopeCriteriaInterface;
use Oro\Bundle\PricingBundle\Sharding\ShardManager;
use Oro\Bundle\ProductBundle\Entity\Product;

/**
 * Fetch prices from Base Price List based DB storage.
 */
abstract class AbstractProductPriceORMStorage implements ProductPriceStorageInterface
{
    /**
     * @var ShardManager
     */
    protected $shardManager;

    /**
     * @var ManagerRegistry
     */
    protected $registry;

    public function __construct(
        ManagerRegistry $registry,
        ShardManager $shardManager
    ) {
        $this->registry = $registry;
        $this->shardManager = $shardManager;
    }

    /**
     * {@inheritdoc}
     */
    public function getPrices(
        ProductPriceScopeCriteriaInterface $scopeCriteria,
        array $products,
        array $productUnitCodes = null,
        array $currencies = null
    ) {
        $priceList = $this->getPriceListByScopeCriteria($scopeCriteria);
        if (!$priceList) {
            return [];
        }

        $productIds = array_map(
            static function ($product) {
                if ($product instanceof Product) {
                    return $product->getId();
                }

                return $product;
            },
            $products
        );

        return $this->getRepository()->getPricesBatch(
            $this->shardManager,
            $priceList->getId(),
            array_filter($productIds),
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

    abstract protected function getRepository(): BaseProductPriceRepository;

    /**
     * @param ProductPriceScopeCriteriaInterface $scopeCriteria
     * @return null|BasePriceList
     */
    abstract protected function getPriceListByScopeCriteria(ProductPriceScopeCriteriaInterface $scopeCriteria);
}
