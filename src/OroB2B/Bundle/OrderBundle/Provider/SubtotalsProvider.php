<?php

namespace OroB2B\Bundle\OrderBundle\Provider;

use Doctrine\Common\Collections\ArrayCollection;

use Symfony\Component\Translation\TranslatorInterface;

use OroB2B\Bundle\OrderBundle\Entity\Order;
use OroB2B\Bundle\OrderBundle\Entity\OrderLineItem;
use OroB2B\Bundle\OrderBundle\Model\Subtotal;
use OroB2B\Bundle\ProductBundle\Rounding\RoundingServiceInterface;

class SubtotalsProvider//todo remove, test
{
    /**
     * @var TranslatorInterface
     */
    protected $translator;

    /**
     * @var RoundingServiceInterface
     */
    protected $rounding;

    /**
     * @param TranslatorInterface $translator
     * @param RoundingServiceInterface $rounding
     */
    public function __construct(TranslatorInterface $translator, RoundingServiceInterface $rounding)
    {
        $this->translator = $translator;
        $this->rounding = $rounding;
    }

    /**
     * Collect all order subtotals
     *
     * @param Order $order
     *
     * @return ArrayCollection|Subtotal[]
     */
    public function getSubtotals(Order $order)
    {
        $subtotals = new ArrayCollection();

        $subtotal = $this->getSubtotal($order);
        $subtotals->set($subtotal->getType(), $subtotal);

        return $subtotals;
    }

    /**
     * Get order subtotal
     *
     * @param Order $order
     *
     * @return Subtotal
     */
    protected function getSubtotal(Order $order)
    {
        $subtotal = new Subtotal();

        $subtotal->setType(Subtotal::TYPE_SUBTOTAL);
        $translation = sprintf('orob2b.order.subtotals.%s', $subtotal->getType());
        $subtotal->setLabel($this->translator->trans($translation));

        $subtotalAmount = 0.0;
        foreach ($order->getLineItems() as $lineItem) {
            if (!$lineItem->getPrice()) {
                continue;
            }
            $rowTotal = $lineItem->getPrice()->getValue();
            if ($lineItem->getPriceType() === OrderLineItem::PRICE_TYPE_UNIT) {
                $rowTotal *= $lineItem->getQuantity();
            }
            if ($order->getCurrency() !== $lineItem->getPrice()->getCurrency()) {
                $rowTotal *= $this->getExchangeRate($lineItem->getPrice()->getCurrency(), $order->getCurrency());
            }
            $subtotalAmount += $rowTotal;
        }

        $subtotal->setAmount(
            $this->rounding->round($subtotalAmount)
        );

        $subtotal->setCurrency($order->getCurrency());

        return $subtotal;
    }

    /**
     * @param string $fromCurrency
     * @param string $toCurrency
     * @return float
     */
    protected function getExchangeRate($fromCurrency, $toCurrency)
    {
        return 1.0;
    }
}
