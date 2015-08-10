<?php

namespace OroB2B\Bundle\OrderBundle\Provider;

use Doctrine\Common\Collections\ArrayCollection;

use Symfony\Component\Translation\TranslatorInterface;

use OroB2B\Bundle\OrderBundle\Entity\Order;
use OroB2B\Bundle\OrderBundle\Model\Subtotal;

class SubtotalsProvider
{
    /**
     * @var TranslatorInterface
     */
    protected $translator;

    /**
     * @param TranslatorInterface $translator
     */
    public function __construct(TranslatorInterface $translator)
    {
        $this->translator = $translator;
    }

    /**
     * Collect all order subtotals
     *
     * @param Order $order
     *
     * @return ArrayCollection
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

        $subtotal->setAmount(mt_rand(1, 1000000)/100);
        $subtotal->setCurrency($order->getCurrency());

        return $subtotal;
    }
}
