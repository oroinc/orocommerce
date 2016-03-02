<?php

namespace OroB2B\Bundle\OrderBundle\Provider;

use Symfony\Component\Translation\TranslatorInterface;

use OroB2B\Bundle\OrderBundle\Entity\Order;
use OroB2B\Bundle\OrderBundle\Model\Subtotal;
use OroB2B\Bundle\ProductBundle\Rounding\RoundingServiceInterface;
use OroB2B\Bundle\OrderBundle\SubtotalProcessor\SubtotalProviderInterface;

class SubtotalShippingCostProvider implements SubtotalProviderInterface
{
    const TYPE = 'shipping_cost';
    const NAME = 'orob2b_order.subtotal_shipping_cost';

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

        $subtotalAmount = 0.0;
        if ($order->getShippingCost()) {
            $subtotalAmount = $order->getShippingCost()->getValue();
        }
        $subtotal->setAmount($this->rounding->round($subtotalAmount));
        $subtotal->setCurrency($order->getCurrency());

        return $subtotal;
    }
}
