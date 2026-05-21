<?php

declare(strict_types=1);

namespace Oro\Bundle\RFPBundle\EventListener\DraftSession;

use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\OrderBundle\Entity\OrderLineItem;
use Oro\Bundle\PricingBundle\Model\ProductLineItemPrice\ProductLineItemPrice;
use Oro\Bundle\PricingBundle\Model\ProductPriceScopeCriteriaFactoryInterface;
use Oro\Bundle\PricingBundle\ProductKit\ProductLineItemPrice\ProductKitLineItemPrice;
use Oro\Bundle\PricingBundle\Provider\ProductLineItemPriceProviderInterface;
use Oro\Bundle\RFPBundle\Entity\Request;
use Oro\Component\DraftSession\Event\EntityDraftCreatedEvent;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\NullLogger;

/**
 * Enriches order draft line items (including kit item line items) with matched prices when the draft is created
 * from an RFQ Request.
 */
class SetMatchedPricesOnOrderDraftCreatedEventListener implements LoggerAwareInterface
{
    use LoggerAwareTrait;

    public function __construct(
        private readonly ProductLineItemPriceProviderInterface $productLineItemPriceProvider,
        private readonly ProductPriceScopeCriteriaFactoryInterface $priceScopeCriteriaFactory
    ) {
        $this->logger = new NullLogger();
    }

    public function onEntityDraftCreated(EntityDraftCreatedEvent $event): void
    {
        $request = $event->getEntity();
        $orderDraft = $event->getDraft();

        if (!$request instanceof Request || !$orderDraft instanceof Order) {
            return;
        }

        $lineItemsWithoutPrices = [];
        foreach ($orderDraft->getLineItems() as $key => $lineItem) {
            if ($lineItem->getProduct() !== null && $lineItem->getPrice() === null) {
                $lineItemsWithoutPrices[$key] = $lineItem;
            }
        }

        $this->applyZeroPriceToFreeFormItems($orderDraft);

        if ($lineItemsWithoutPrices === []) {
            return;
        }

        $scopeCriteria = $this->priceScopeCriteriaFactory->createByContext($orderDraft);

        try {
            $lineItemPrices = $this->productLineItemPriceProvider
                ->getProductLineItemsPrices($lineItemsWithoutPrices, $scopeCriteria, $orderDraft->getCurrency());
        } catch (\Throwable $e) {
            $this->logger->error(
                'Failed to fetch matched prices for order draft line items.',
                ['exception' => $e]
            );

            return;
        }

        $this->applyMatchedPrices($lineItemsWithoutPrices, $lineItemPrices);
    }

    /**
     * @param array<int, OrderLineItem> $lineItemsWithoutPrices
     * @param array<int, ProductLineItemPrice> $lineItemPrices
     */
    private function applyMatchedPrices(array $lineItemsWithoutPrices, array $lineItemPrices): void
    {
        foreach ($lineItemsWithoutPrices as $key => $lineItem) {
            if (!isset($lineItemPrices[$key])) {
                continue;
            }

            $this->applyPrice($lineItem, $lineItemPrices[$key]);
        }
    }

    private function applyPrice(OrderLineItem $lineItem, ProductLineItemPrice $lineItemPrice): void
    {
        $lineItem->setPrice(clone $lineItemPrice->getPrice());

        if (!$lineItemPrice instanceof ProductKitLineItemPrice) {
            return;
        }

        foreach ($lineItem->getKitItemLineItems() as $kitItemLineItem) {
            $kitItemLineItemPrice = $lineItemPrice->getKitItemLineItemPrice($kitItemLineItem);
            if ($kitItemLineItemPrice === null) {
                continue;
            }

            $kitItemLineItem->setPrice(clone $kitItemLineItemPrice->getPrice());
        }
    }

    /**
     * Sets a zero placeholder price on free-form line items (i.e., items whose product has been deleted).
     * Without a product there is no tier price to resolve automatically; the zero placeholder
     * is overwritten by the administrator before the order is saved.
     */
    private function applyZeroPriceToFreeFormItems(Order $orderDraft): void
    {
        $currency = $orderDraft->getCurrency();
        if ($currency === null) {
            return;
        }

        foreach ($orderDraft->getLineItems() as $lineItem) {
            if (
                $lineItem->getProduct() === null
                && $lineItem->getFreeFormProduct() !== null
                && $lineItem->getPrice() === null
            ) {
                $lineItem->setPrice(Price::create(0, $currency));
            }
        }
    }
}
