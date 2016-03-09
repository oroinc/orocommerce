<?php

namespace OroB2B\Bundle\OrderBundle\Provider;

use Symfony\Component\Translation\TranslatorInterface;

use OroB2B\Bundle\OrderBundle\Entity\Order;
use OroB2B\Bundle\OrderBundle\Entity\OrderLineItem;
use OroB2B\Bundle\OrderBundle\Model\Subtotal;
use OroB2B\Bundle\ProductBundle\Rounding\RoundingServiceInterface;
use OroB2B\Bundle\OrderBundle\SubtotalProcessor\SubtotalProviderInterface;

class SubtotalLineItemProvider implements SubtotalProviderInterface
{
    const TYPE = 'subtotal';
    const NAME = 'orob2b_order.subtotal_lineitem';

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
     * {@inheritdoc}
     */
    public function getName()
    {
        return self::NAME;
    }

    /**
     * {@inheritdoc}
     */
    public function getSubtotal(Order $order)
    {
        $subtotal = new Subtotal();

        $subtotal->setType(self::TYPE);
        $translation = sprintf('orob2b.order.subtotals.%s', $subtotal->getType());
        $subtotal->setLabel($this->translator->trans($translation));
        $subtotal->setVisible(true);

        $subtotalAmount = 0.0;
        foreach ($order->getLineItems() as $lineItem) {
            if (!$lineItem->getPrice()) {
                continue;
            }
            $rowTotal = $lineItem->getPrice()->getValue();
            if ((int)$lineItem->getPriceType() === OrderLineItem::PRICE_TYPE_UNIT) {
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
