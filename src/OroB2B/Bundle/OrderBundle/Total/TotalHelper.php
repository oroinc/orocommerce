<?php

namespace OroB2B\Bundle\OrderBundle\Total;

use Oro\Bundle\CurrencyBundle\Entity\Price;

use OroB2B\Bundle\OrderBundle\Entity\Order;
use OroB2B\Bundle\OrderBundle\Entity\OrderDiscount;
use OroB2B\Bundle\OrderBundle\Provider\DiscountSubtotalProvider;
use OroB2B\Bundle\PricingBundle\SubtotalProcessor\Model\Subtotal;
use OroB2B\Bundle\PricingBundle\SubtotalProcessor\Provider\LineItemSubtotalProvider;
use OroB2B\Bundle\PricingBundle\SubtotalProcessor\TotalProcessorProvider;

class TotalHelper
{
    /** @var TotalProcessorProvider */
    protected $totalProvider;

    /** @var LineItemSubtotalProvider */
    protected $lineItemSubtotalProvider;

    /** @var DiscountSubtotalProvider */
    protected $discountSubtotalProvider;

    /**
     * @param TotalProcessorProvider $totalProvider
     * @param LineItemSubtotalProvider $lineItemSubtotalProvider
     * @param DiscountSubtotalProvider $discountSubtotalProvider
     */
    public function __construct(
        TotalProcessorProvider $totalProvider,
        LineItemSubtotalProvider $lineItemSubtotalProvider,
        DiscountSubtotalProvider $discountSubtotalProvider
    ) {
        $this->totalProvider = $totalProvider;
        $this->lineItemSubtotalProvider = $lineItemSubtotalProvider;
        $this->discountSubtotalProvider = $discountSubtotalProvider;
    }

    /**
     * @param Order $order
     */
    public function fillSubtotals(Order $order)
    {
        $subtotal = $this->lineItemSubtotalProvider->getSubtotal($order);

        $order->setSubtotal($subtotal->getAmount());
        if ($subtotal->getAmount() > 0) {
            foreach ($order->getDiscounts() as $discount) {
                if ($discount->getType() === OrderDiscount::TYPE_AMOUNT) {
                    $discount->setPercent($this->calculatePercent($subtotal, $discount));
                }
            }
        }
    }

    /**
     * @param Order $order
     */
    public function fillDiscounts(Order $order)
    {
        $discountSubtotals = $this->discountSubtotalProvider->getSubtotal($order);

        $discountSubtotalAmount = new Price();
        if (count($discountSubtotals) > 0) {
            foreach ($discountSubtotals as $discount) {
                $newAmount = $discount->getAmount() + (float) $discountSubtotalAmount->getValue();
                $discountSubtotalAmount->setValue($newAmount);
            }
        }
        $order->setTotalDiscounts($discountSubtotalAmount);
    }

    /**
     * @param Order $order
     */
    public function fillTotal(Order $order)
    {
        $total = $this->totalProvider->getTotal($order);
        if ($total) {
            $order->setTotal($total->getAmount());
        } else {
            $order->setTotal(0.0);
        }
    }

    /**
     * @param Subtotal $subtotal
     * @param OrderDiscount $discount
     * @return int
     */
    protected function calculatePercent($subtotal, $discount)
    {
        return (float) ($discount->getAmount() / $subtotal->getAmount() * 100);
    }
}
