<?php

namespace OroB2B\Bundle\OrderBundle\Provider;

use Doctrine\Common\Collections\ArrayCollection;

use Symfony\Component\Translation\TranslatorInterface;

use OroB2B\Bundle\OrderBundle\Entity\Order;
use OroB2B\Bundle\OrderBundle\Model\Total;

class TotalsProvider
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

    public function getTotals(Order $order)
    {
        $totals = new ArrayCollection();

        $totals->set('subtotal', $this->getSubtotal($order));

        return $totals;
    }

    private function getSubtotal(Order $order)
    {
        $subtotal = new Total();

        $subtotal->setName('subtotal');
        $translation = sprintf('orob2b.order.totals.%s', $subtotal->getName());
        $subtotal->setLabel($this->translator->trans($translation));

        $subtotal->setAmount(0);
        $subtotal->setCurrency($order->getCurrency());

        return $subtotal;
    }
}
