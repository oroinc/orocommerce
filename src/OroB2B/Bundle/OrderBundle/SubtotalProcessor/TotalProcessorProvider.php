<?php

namespace OroB2B\Bundle\OrderBundle\SubtotalProcessor;

use Doctrine\Common\Collections\ArrayCollection;

use Symfony\Component\Translation\TranslatorInterface;

use OroB2B\Bundle\OrderBundle\Entity\Order;
use OroB2B\Bundle\OrderBundle\Model\Subtotal;
use OroB2B\Bundle\ProductBundle\Rounding\RoundingServiceInterface;

class TotalProcessorProvider
{
    const NAME = 'orob2b_order.subtotal_total';
    const TYPE = 'total';

    /** @var SubtotalProviderRegistry */
    protected $subtotalProviderRegistry;

    /** @var TranslatorInterface */
    protected $translator;

    /** @var RoundingServiceInterface */
    protected $rounding;

    /** @var  [] */
    protected $subtotals;

    /**
     * @param SubtotalProviderRegistry $subtotalProviderRegistry
     * @param TranslatorInterface $translator
     * @param RoundingServiceInterface $rounding
     */
    public function __construct(
        SubtotalProviderRegistry $subtotalProviderRegistry,
        TranslatorInterface $translator,
        RoundingServiceInterface $rounding
    ) {
        $this->subtotalProviderRegistry = $subtotalProviderRegistry;
        $this->translator = $translator;
        $this->rounding = $rounding;
        $this->subtotals = [];
    }

    public function getName()
    {
        return self::NAME;
    }

    /**
     * Calculate and return total based on all subtotals
     *
     * @param Order $order
     *
     * @return Subtotal
     */
    public function getTotal(Order $order)
    {
        $total = new Subtotal();

        $total->setType(self::TYPE);
        $translation = sprintf('orob2b.order.subtotals.%s', $total->getType());
        $total->setLabel($this->translator->trans($translation));

        $totalAmount = 0.0;
        foreach ($this->getSubtotals($order) as $subtotal) {
            $rowTotal = $subtotal->getAmount();

            if ($order->getCurrency() !== $subtotal->getCurrency()) {
                $rowTotal *= $this->getExchangeRate($subtotal->getCurrency(), $order->getCurrency());
            }
            $totalAmount += $rowTotal;
        }
        $total->setAmount($this->rounding->round($totalAmount));
        $total->setCurrency($order->getCurrency());

        return $total;
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
        $hash = spl_object_hash($order);

        if (!array_key_exists($hash, $this->subtotals)) {
            foreach ($this->subtotalProviderRegistry->getProviders() as $provider) {
                $subtotal = $provider->getSubtotal($order);
                $subtotals->set($subtotal->getType(), $subtotal);
            }
            $this->subtotals[$hash] = $subtotals;
        }

        return $this->subtotals[$hash];
    }

    /**
     * Clear subtotals cache
     */
    public function clearCache()
    {
        $this->subtotals = [];
    }

    /**
     * @param string $fromCurrency
     * @param string $toCurrency
     *
     * @return float
     */
    protected function getExchangeRate($fromCurrency, $toCurrency)
    {
        return 1.0;
    }
}
