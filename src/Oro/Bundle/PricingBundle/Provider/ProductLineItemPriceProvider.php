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
use Oro\Bundle\ProductBundle\Model\ProductLineItemInterface;
use Oro\Bundle\ProductBundle\Model\ProductLineItemsHolderInterface;

/**
 * Provides an array of {@see ProductLineItemPrice} objects for the given line items or line items aware object.
 */
class ProductLineItemPriceProvider implements ProductLineItemPriceProviderInterface
{
    private MatchedProductPriceProviderInterface $matchedProductPriceProvider;

    private ProductPriceScopeCriteriaFactoryInterface $priceScopeCriteriaFactory;

    private ProductPriceScopeCriteriaRequestHandler $priceScopeCriteriaRequestHandler;

    private ProductPriceCriteriaFactoryInterface $productPriceCriteriaFactory;

    private ProductLineItemsHolderCurrencyProvider $productLineItemsHolderCurrencyProvider;

    private UserCurrencyManager $userCurrencyManager;

    private ProductLineItemPriceFactoryInterface $productLineItemPriceFactory;

    public function __construct(
        MatchedProductPriceProviderInterface $matchedProductPriceProvider,
        ProductPriceScopeCriteriaFactoryInterface $productPriceScopeCriteriaFactory,
        ProductPriceScopeCriteriaRequestHandler $priceScopeCriteriaRequestHandler,
        ProductPriceCriteriaFactoryInterface $productPriceCriteriaFactory,
        ProductLineItemsHolderCurrencyProvider $productLineItemsHolderCurrencyProvider,
        UserCurrencyManager $userCurrencyManager,
        ProductLineItemPriceFactoryInterface $productLineItemPriceFactory
    ) {
        $this->matchedProductPriceProvider = $matchedProductPriceProvider;
        $this->priceScopeCriteriaFactory = $productPriceScopeCriteriaFactory;
        $this->priceScopeCriteriaRequestHandler = $priceScopeCriteriaRequestHandler;
        $this->productPriceCriteriaFactory = $productPriceCriteriaFactory;
        $this->productLineItemsHolderCurrencyProvider = $productLineItemsHolderCurrencyProvider;
        $this->userCurrencyManager = $userCurrencyManager;
        $this->productLineItemPriceFactory = $productLineItemPriceFactory;
    }

    /**
     * Provides {@see ProductLineItemPrice} objects for the specified $lineItems.
     *
     * @param iterable<ProductLineItemInterface> $lineItems
     * @param string|null $currency When null - currency is detected automatically from the current context.
     * @param ProductPriceScopeCriteriaInterface|null $priceScopeCriteria
     *
     * @return array<int|string,ProductLineItemPrice> Array of product line item prices, each element
     *  associated with the key of the corresponding line item from $lineItems
     */
    #[\Override]
    public function getProductLineItemsPrices(
        iterable $lineItems,
        ?ProductPriceScopeCriteriaInterface $priceScopeCriteria = null,
        ?string $currency = null
    ): array {
        if ($currency === null) {
            $currency = $this->userCurrencyManager->getUserCurrency() ?:
                $this->userCurrencyManager->getDefaultCurrency();
        }

        if ($priceScopeCriteria === null) {
            $priceScopeCriteria = $this->priceScopeCriteriaRequestHandler->getPriceScopeCriteria();
        }

        $productsPriceCriteria = $this->productPriceCriteriaFactory
            ->createListFromProductLineItems($lineItems, $currency);
        if (!$productsPriceCriteria) {
            return [];
        }

        $matchedProductPrices = $this->matchedProductPriceProvider
            ->getMatchedProductPrices($productsPriceCriteria, $priceScopeCriteria);

        $productLineItemsPrices = [];
        foreach ($lineItems as $key => $lineItem) {
            if (!isset($productsPriceCriteria[$key])) {
                continue;
            }

            $productPriceCriterionIdentifier = $productsPriceCriteria[$key]?->getIdentifier();
            if (!isset($matchedProductPrices[$productPriceCriterionIdentifier])) {
                continue;
            }

            $productLineItemPrice = $this->productLineItemPriceFactory
                ->createForProductLineItem($lineItem, $matchedProductPrices[$productPriceCriterionIdentifier]);

            if ($productLineItemPrice !== null) {
                $productLineItemsPrices[$key] = $productLineItemPrice;
            }
        }

        return $productLineItemsPrices;
    }

    /**
     * Provides {@see ProductLineItemPrice} objects for the specified $lineItemsHolder.
     *
     * @param ProductLineItemsHolderInterface|LineItemsAwareInterface|LineItemsNotPricedAwareInterface $lineItemsHolder
     * @param string|null $currency When null - currency is detected automatically by $lineItemsHolder.
     *
     * @return array<int|string,ProductLineItemPrice> Array of product line item prices, each element
     *  associated with the key of the corresponding line item from $lineItemsHolder::getLineItems()
     */
    #[\Override]
    public function getProductLineItemsPricesForLineItemsHolder(
        ProductLineItemsHolderInterface|LineItemsAwareInterface|LineItemsNotPricedAwareInterface $lineItemsHolder,
        ?string $currency = null
    ): array {
        if ($currency === null) {
            $currency = $this->productLineItemsHolderCurrencyProvider->getCurrencyForLineItemsHolder($lineItemsHolder);
        }

        $priceScopeCriteria = $this->priceScopeCriteriaFactory->createByContext($lineItemsHolder);

        return $this->getProductLineItemsPrices($lineItemsHolder->getLineItems(), $priceScopeCriteria, $currency);
    }
}
