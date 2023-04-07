<?php

namespace Oro\Bundle\PricingBundle\Provider;

use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\PricingBundle\Model\ProductPriceCriteria;
use Oro\Bundle\PricingBundle\Model\ProductPriceScopeCriteriaFactoryInterface;
use Oro\Bundle\PricingBundle\Model\ProductPriceScopeCriteriaInterface;
use Oro\Bundle\PricingBundle\SubtotalProcessor\Model\LineItemsAwareInterface;
use Oro\Bundle\PricingBundle\SubtotalProcessor\Model\LineItemsNotPricedAwareInterface;

/**
 * Provides prices for line items holder.
 */
class ProductLineItemsHolderPricesProvider
{
    private ProductPriceProviderInterface $productPriceProvider;

    private ProductPriceScopeCriteriaFactoryInterface $priceScopeCriteriaFactory;

    private ProductLineItemsHolderPriceCriteriaProvider $lineItemsHolderPriceCriteriaProvider;

    public function __construct(
        ProductPriceProviderInterface $productPriceProvider,
        ProductPriceScopeCriteriaFactoryInterface $productPriceScopeCriteriaFactory,
        ProductLineItemsHolderPriceCriteriaProvider $productLineItemsHolderPriceCriteriaProvider
    ) {
        $this->productPriceProvider = $productPriceProvider;
        $this->priceScopeCriteriaFactory = $productPriceScopeCriteriaFactory;
        $this->lineItemsHolderPriceCriteriaProvider = $productLineItemsHolderPriceCriteriaProvider;
    }

    /**
     * @param LineItemsAwareInterface|LineItemsNotPricedAwareInterface $lineItemsHolder
     * @param string|null $currency
     *
     * @return array{
     *     array<string,Price>,
     *     array<string,ProductPriceCriteria>,
     *     ProductPriceScopeCriteriaInterface
     *  }
     */
    public function getMatchedPricesForLineItemsHolder(
        LineItemsAwareInterface|LineItemsNotPricedAwareInterface $lineItemsHolder,
        ?string $currency = null
    ): array {
        $productsPriceCriteria = $this->lineItemsHolderPriceCriteriaProvider
            ->getProductPriceCriteriaForLineItemsHolder($lineItemsHolder, $currency);
        $priceScopeCriteria = $this->priceScopeCriteriaFactory->createByContext($lineItemsHolder);

        if (!$productsPriceCriteria) {
            return [[], [], $priceScopeCriteria];
        }

        return [
            $this->productPriceProvider->getMatchedPrices($productsPriceCriteria, $priceScopeCriteria),
            $productsPriceCriteria,
            $priceScopeCriteria
        ];
    }
}
