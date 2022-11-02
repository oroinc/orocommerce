<?php

namespace Oro\Bundle\PricingBundle\Storage;

use Oro\Bundle\FeatureToggleBundle\Checker\FeatureChecker;
use Oro\Bundle\PricingBundle\Model\ProductPriceScopeCriteriaInterface;

/**
 * Fetch prices from Base Price List based DB storage.
 */
class CompositeProductPriceStorage implements ProductPriceStorageInterface
{
    /**
     * @var ProductPriceStorageInterface
     */
    private $flatPricingStorage;

    /**
     * @var ProductPriceStorageInterface
     */
    private $combinedPricingStorage;

    /**
     * @var FeatureChecker
     */
    private $featureChecker;

    public function __construct(
        ProductPriceStorageInterface $flatPricingStorage,
        ProductPriceStorageInterface $combinedPricingStorage,
        FeatureChecker $featureChecker
    ) {
        $this->flatPricingStorage = $flatPricingStorage;
        $this->combinedPricingStorage = $combinedPricingStorage;
        $this->featureChecker = $featureChecker;
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
        $storage = $this->getStorage();
        if ($storage) {
            return $storage->getPrices($scopeCriteria, $products, $productUnitCodes, $currencies);
        }

        return [];
    }

    /**
     * {@inheritdoc}
     */
    public function getSupportedCurrencies(ProductPriceScopeCriteriaInterface $scopeCriteria)
    {
        $storage = $this->getStorage();
        if ($storage) {
            return $storage->getSupportedCurrencies($scopeCriteria);
        }

        return [];
    }

    private function getStorage(): ?ProductPriceStorageInterface
    {
        if ($this->featureChecker->isFeatureEnabled('oro_price_lists_combined')) {
            return $this->combinedPricingStorage;
        }

        if ($this->featureChecker->isFeatureEnabled('oro_price_lists_flat')) {
            return $this->flatPricingStorage;
        }

        return null;
    }
}
