<?php

namespace Oro\Bundle\CheckoutBundle\Provider\MultiShipping\Total;

use Oro\Bundle\CheckoutBundle\Provider\MultiShipping\SplitEntitiesProviderInterface;
use Oro\Bundle\CurrencyBundle\Rounding\RoundingServiceInterface;
use Oro\Bundle\PricingBundle\SubtotalProcessor\Model\Subtotal;
use Oro\Bundle\PricingBundle\SubtotalProcessor\Model\SubtotalProviderInterface;
use Oro\Bundle\ProductBundle\Model\ProductLineItemsHolderInterface;

/**
 * Calculate promotion totals for Orders with subOrders and Checkout with supported split checkout functionality.
 */
class PromotionSubtotalProviderDecorator implements SubtotalProviderInterface
{
    public function __construct(
        private SubtotalProviderInterface $subtotalProvider,
        private RoundingServiceInterface $rounding,
        private SplitEntitiesProviderInterface $splitEntitiesProvider
    ) {
    }

    /**
     * Subtotal for Orders with subOrders should be calculated as the sum of its subOrders subtotals.
     *
     */
    #[\Override]
    public function getSubtotal($entity)
    {
        if ($entity instanceof ProductLineItemsHolderInterface) {
            $subEntities = $this->splitEntitiesProvider->getSplitEntities($entity);
            if (!empty($subEntities)) {
                $totals = [];
                foreach ($subEntities as $subEntity) {
                    $subOrderTotal = $this->subtotalProvider->getSubtotal($subEntity);
                    $totals = $this->calculateTotals($totals, $subOrderTotal);
                }

                return $totals;
            }
        }

        return $this->subtotalProvider->getSubtotal($entity);
    }

    private function calculateTotals(array $totals, array $subOrderTotals): array
    {
        /**
         * @var string $totalKey
         * @var Subtotal $total
         */
        foreach ($subOrderTotals as $totalKey => $total) {
            if (!array_key_exists($totalKey, $totals)) {
                $totals[$totalKey] = $total;

                continue;
            }

            /** @var Subtotal $subtotal */
            $subtotal = $totals[$totalKey];
            $subtotal->setAmount($this->rounding->round($subtotal->getAmount() + $total->getAmount()));
            $subtotal->setVisible($subtotal->getAmount() > 0.0);
        }

        return $totals;
    }

    #[\Override]
    public function isSupported($entity): bool
    {
        return $this->subtotalProvider->isSupported($entity);
    }
}
