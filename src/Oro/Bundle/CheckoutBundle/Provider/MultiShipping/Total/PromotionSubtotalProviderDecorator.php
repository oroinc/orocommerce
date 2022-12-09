<?php

namespace Oro\Bundle\CheckoutBundle\Provider\MultiShipping\Total;

use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CheckoutBundle\Provider\MultiShipping\SplitCheckoutProvider;
use Oro\Bundle\CurrencyBundle\Rounding\RoundingServiceInterface;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\PricingBundle\SubtotalProcessor\Model\Subtotal;
use Oro\Bundle\PricingBundle\SubtotalProcessor\Model\SubtotalProviderInterface;

/**
 * Calculate promotion totals for Orders with subOrders and Checkout with supported split checkout functionality.
 */
class PromotionSubtotalProviderDecorator implements SubtotalProviderInterface
{
    private SubtotalProviderInterface $subtotalProvider;
    private RoundingServiceInterface $rounding;
    private SplitCheckoutProvider $splitCheckoutProvider;

    public function __construct(
        SubtotalProviderInterface $subtotalProvider,
        RoundingServiceInterface $rounding,
        SplitCheckoutProvider $splitCheckoutProvider
    ) {
        $this->subtotalProvider = $subtotalProvider;
        $this->rounding = $rounding;
        $this->splitCheckoutProvider = $splitCheckoutProvider;
    }

    /**
     * Subtotal for Orders with subOrders should be calculated as the sum of its subOrders subtotals.
     *
     * {@inheritdoc}
     */
    public function getSubtotal($entity)
    {
        $subEntities = $this->getSubEntities($entity);
        if (!empty($subEntities)) {
            $totals = [];
            foreach ($subEntities as $subEntity) {
                $subOrderTotal = $this->subtotalProvider->getSubtotal($subEntity);
                $totals = $this->calculateTotals($totals, $subOrderTotal);
            }

            return $totals;
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

    private function getSubEntities($entity): array
    {
        if ($entity instanceof Checkout) {
            return $this->splitCheckoutProvider->getSubCheckouts($entity);
        }

        if ($entity instanceof Order) {
            return $entity->getSubOrders()->toArray();
        }

        return [];
    }

    /**
     * {@inheritdoc}
     */
    public function isSupported($entity)
    {
        return $this->subtotalProvider->isSupported($entity);
    }
}
