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

    public function getSubtotals(Order $order)
    {
        $subtotals = new ArrayCollection();

        $subtotals->set('subtotal', $this->getSubtotal($order));

        return $subtotals;
    }

    private function getSubtotal(Order $order)
    {
        $subtotal = new Subtotal();

        $subtotal->setName('subtotal');
        $translation = sprintf('orob2b.order.subtotals.%s', $subtotal->getName());
        $subtotal->setLabel($this->translator->trans($translation));

        $subtotal->setAmount(0);
        $subtotal->setCurrency($order->getCurrency());

        return $subtotal;
    }
}
