<?php

namespace Oro\Bundle\OrderBundle\Pricing;

use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\OrderBundle\Entity\OrderLineItem;
use Oro\Bundle\PricingBundle\Model\ProductLineItemPrice\ProductLineItemPrice;
use Oro\Bundle\PricingBundle\Model\ProductPriceCriteria;
use Oro\Bundle\PricingBundle\Model\ProductPriceCriteriaFactoryInterface;
use Oro\Bundle\PricingBundle\Model\ProductPriceScopeCriteriaFactoryInterface;
use Oro\Bundle\PricingBundle\ProductKit\ProductLineItemPrice\ProductKitLineItemPrice;
use Oro\Bundle\PricingBundle\Provider\MatchingPriceProvider;
use Oro\Bundle\PricingBundle\Provider\ProductLineItemPriceProviderInterface;
use Psr\Log\LoggerInterface;

/**
 * Match prices by order line items.
 */
class PriceMatcher
{
    /** @var MatchingPriceProvider */
    protected $provider;

    /** @var ProductPriceScopeCriteriaFactoryInterface */
    protected $priceScopeCriteriaFactory;

    /** @var LoggerInterface */
    private $logger;

    private ?ProductPriceCriteriaFactoryInterface $productPriceCriteriaFactory = null;

    private ?ProductLineItemPriceProviderInterface $productLineItemPriceProvider = null;

    public function __construct(
        MatchingPriceProvider $provider,
        ProductPriceScopeCriteriaFactoryInterface $priceScopeCriteriaFactory,
        LoggerInterface $logger
    ) {
        $this->provider = $provider;
        $this->priceScopeCriteriaFactory = $priceScopeCriteriaFactory;
        $this->logger = $logger;
    }

    public function setProductPriceCriteriaFactory(
        ?ProductPriceCriteriaFactoryInterface $productPriceCriteriaFactory
    ): void {
        $this->productPriceCriteriaFactory = $productPriceCriteriaFactory;
    }

    public function setProductLineItemPriceProvider(
        ProductLineItemPriceProviderInterface $productLineItemPriceProvider
    ): void {
        $this->productLineItemPriceProvider = $productLineItemPriceProvider;
    }

    /**
     * @deprecated since 5.1
     * @param Order $order
     * @return array
     */
    public function getMatchingPrices(Order $order)
    {
        $lineItems = $order->getLineItems()->map(
            function (OrderLineItem $orderLineItem) use ($order) {
                $product = $orderLineItem->getProduct();

                return [
                    'product' => $product ? $product->getId() : null,
                    'unit' => $orderLineItem->getProductUnit() ? $orderLineItem->getProductUnit()->getCode() : null,
                    'qty' => $orderLineItem->getQuantity(),
                    'currency' => $orderLineItem->getCurrency() ?: $order->getCurrency(),
                ];
            }
        );

        return $this->provider->getMatchingPrices(
            $lineItems->toArray(),
            $this->priceScopeCriteriaFactory->createByContext($order)
        );
    }

    /**
     * @deprecated since 5.1
     */
    public function fillMatchingPrices(Order $order, array $matchedPrices = [])
    {
        $lineItems = $order->getLineItems()->toArray();

        if ($this->productPriceCriteriaFactory === null) {
            // BC fallback.
            array_walk(
                $lineItems,
                function (OrderLineItem $orderLineItem) use ($matchedPrices) {
                    $productPriceCriteria = $this->createProductPriceCriteria($orderLineItem);
                    if (!$productPriceCriteria) {
                        return;
                    }

                    $identifier = $productPriceCriteria->getIdentifier();
                    if (array_key_exists($identifier, $matchedPrices)) {
                        $this->fillOrderLineItemData($orderLineItem, $matchedPrices[$identifier]);
                    }
                }
            );
        } else {
            $productsPriceCriteria = $this->productPriceCriteriaFactory->createListFromProductLineItems(
                $lineItems,
                $order->getCurrency()
            );

            foreach ($productsPriceCriteria as $lineItemIdx => $productPriceCriteria) {
                $identifier = $productPriceCriteria->getIdentifier();
                if (array_key_exists($identifier, $matchedPrices)) {
                    $this->fillOrderLineItemData($lineItems[$lineItemIdx], $matchedPrices[$identifier]);
                }
            }
        }
    }

    public function addMatchingPrices(Order $order)
    {
        if ($this->productLineItemPriceProvider === null) {
            $matchedPrices = $this->getMatchingPrices($order);

            $this->fillMatchingPrices($order, $matchedPrices);
        } else {
            $lineItems = [];
            foreach ($order->getLineItems() as $key => $lineItem) {
                if ($lineItem->getProduct() === null) {
                    continue;
                }

                if ($lineItem->getCurrency() === null || $lineItem->getValue() === null) {
                    $lineItems[$key] = $lineItem;
                }
            }

            if ($lineItems) {
                $productLineItemsPrices = $this->productLineItemPriceProvider->getProductLineItemsPrices(
                    $lineItems,
                    $this->priceScopeCriteriaFactory->createByContext($order),
                    $order->getCurrency()
                );

                foreach ($lineItems as $key => $lineItem) {
                    if (!isset($productLineItemsPrices[$key])) {
                        continue;
                    }

                    $this->fillPrice($lineItem, $productLineItemsPrices[$key]);
                }
            }
        }
    }

    /**
     * @deprecated since 5.1
     */
    protected function fillOrderLineItemData(OrderLineItem $orderLineItem, array $matchedPrice = [])
    {
        if (null === $orderLineItem->getCurrency() && !empty($matchedPrice['currency'])) {
            $orderLineItem->setCurrency((string)$matchedPrice['currency']);
        }
        if (null === $orderLineItem->getValue() && !empty($matchedPrice['value'])) {
            $orderLineItem->setValue((string)$matchedPrice['value']);
        }
    }

    /**
     * @deprecated since 5.1
     * @param OrderLineItem $orderLineItem
     * @return null|ProductPriceCriteria
     */
    protected function createProductPriceCriteria(OrderLineItem $orderLineItem)
    {
        $product = $orderLineItem->getProduct();
        $productUnit = $orderLineItem->getProductUnit();

        if (!$product || !$productUnit) {
            return null;
        }

        try {
            return new ProductPriceCriteria(
                $product,
                $productUnit,
                $orderLineItem->getQuantity(),
                $orderLineItem->getCurrency() ?: $orderLineItem->getOrder()->getCurrency()
            );
        } catch (\InvalidArgumentException $e) {
            $this->logger->error(
                'Got error while trying to create new ProductPriceCriteria with message: "{message}"',
                [
                    'message' => $e->getMessage(),
                    'exception' => $e
                ]
            );
            return null;
        }
    }

    private function fillPrice(OrderLineItem $lineItem, ProductLineItemPrice $productLineItemPrice): void
    {
        $lineItem->setPrice(clone($productLineItemPrice->getPrice()));
        if ($lineItem->getProduct()->isKit() !== true) {
            return;
        }

        if (!$productLineItemPrice instanceof ProductKitLineItemPrice) {
            return;
        }

        foreach ($lineItem->getKitItemLineItems() as $kitItemLineItem) {
            $kitItemLineItemPrice = $productLineItemPrice->getKitItemLineItemPrice($kitItemLineItem);
            if ($kitItemLineItemPrice === null) {
                continue;
            }

            $kitItemLineItem->setPrice(clone($kitItemLineItemPrice->getPrice()));
        }
    }
}
