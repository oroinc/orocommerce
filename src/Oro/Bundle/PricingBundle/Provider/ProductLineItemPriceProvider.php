<?php

namespace Oro\Bundle\PricingBundle\Provider;

use Oro\Bundle\PricingBundle\Manager\UserCurrencyManager;
use Oro\Bundle\PricingBundle\Model\ProductLineItemPrice\Factory\ProductLineItemPriceFactoryInterface;
use Oro\Bundle\PricingBundle\Model\ProductLineItemPrice\ProductLineItemPrice;
use Oro\Bundle\PricingBundle\Model\ProductPriceCriteriaFactoryInterface;
use Oro\Bundle\PricingBundle\Model\ProductPriceScopeCriteriaFactoryInterface;
use Oro\Bundle\PricingBundle\Model\ProductPriceScopeCriteriaInterface;
use Oro\Bundle\PricingBundle\Model\ProductPriceScopeCriteriaRequestHandler;
use Oro\Bundle\PricingBundle\SubtotalProcessor\Model\LineItemsAwareInterface;
use Oro\Bundle\PricingBundle\SubtotalProcessor\Model\LineItemsNotPricedAwareInterface;
use Oro\Bundle\ProductBundle\Model\ProductLineItemsHolderInterface;

/**
 * Provides an array of {@see ProductLineItemPrice} objects for the given line items or line items aware object.
 */
class ProductLineItemPriceProvider implements ProductLineItemPriceProviderInterface
{
    public function __construct(
        private readonly MatchedProductPriceProviderInterface $matchedProductPriceProvider,
        private readonly ProductPriceScopeCriteriaFactoryInterface $productPriceScopeCriteriaFactory,
        private readonly ProductPriceScopeCriteriaRequestHandler $priceScopeCriteriaRequestHandler,
        private readonly ProductPriceCriteriaFactoryInterface $productPriceCriteriaFactory,
        private readonly ProductLineItemsHolderCurrencyProvider $productLineItemsHolderCurrencyProvider,
        private readonly UserCurrencyManager $userCurrencyManager,
        private readonly ProductLineItemPriceFactoryInterface $productLineItemPriceFactory
    ) {
    }

    #[\Override]
    public function getProductLineItemsPrices(
        iterable $lineItems,
        ?ProductPriceScopeCriteriaInterface $priceScopeCriteria = null,
        ?string $currency = null
    ): array {
        if (null === $currency) {
            $currency = $this->userCurrencyManager->getUserCurrency()
                ?: $this->userCurrencyManager->getDefaultCurrency();
        }

        $productsPriceCriteria = $this->productPriceCriteriaFactory->createListFromProductLineItems(
            $lineItems,
            $currency
        );
        if (!$productsPriceCriteria) {
            return [];
        }

        $matchedProductPrices = $this->matchedProductPriceProvider->getMatchedProductPrices(
            $productsPriceCriteria,
            $priceScopeCriteria ?? $this->priceScopeCriteriaRequestHandler->getPriceScopeCriteria()
        );

        $productLineItemsPrices = [];
        foreach ($lineItems as $key => $lineItem) {
            if (!isset($productsPriceCriteria[$key])) {
                continue;
            }

            $productPriceCriterionIdentifier = $productsPriceCriteria[$key]->getIdentifier();
            if (!isset($matchedProductPrices[$productPriceCriterionIdentifier])) {
                continue;
            }

            $productLineItemPrice = $this->productLineItemPriceFactory->createForProductLineItem(
                $lineItem,
                $matchedProductPrices[$productPriceCriterionIdentifier]
            );
            if (null !== $productLineItemPrice) {
                $productLineItemsPrices[$key] = $productLineItemPrice;
            }
        }

        return $productLineItemsPrices;
    }

    #[\Override]
    public function getProductLineItemsPricesForLineItemsHolder(
        ProductLineItemsHolderInterface|LineItemsAwareInterface|LineItemsNotPricedAwareInterface $lineItemsHolder,
        ?string $currency = null
    ): array {
        return $this->getProductLineItemsPrices(
            $lineItemsHolder->getLineItems(),
            $this->productPriceScopeCriteriaFactory->createByContext($lineItemsHolder),
            $currency ?? $this->productLineItemsHolderCurrencyProvider->getCurrencyForLineItemsHolder($lineItemsHolder)
        );
    }
}
