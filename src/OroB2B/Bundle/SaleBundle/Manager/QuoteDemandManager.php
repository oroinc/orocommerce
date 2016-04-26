<?php

namespace OroB2B\Bundle\SaleBundle\Manager;

use OroB2B\Bundle\PricingBundle\SubtotalProcessor\Provider\LineItemSubtotalProvider;
use OroB2B\Bundle\PricingBundle\SubtotalProcessor\TotalProcessorProvider;
use OroB2B\Bundle\SaleBundle\Entity\QuoteDemand;

class QuoteDemandManager
{
    /**
     * @var TotalProcessorProvider
     */
    protected $totalProvider;

    /**
     * @var LineItemSubtotalProvider
     */
    protected $subtotalProvider;

    /**
     * @param TotalProcessorProvider $totalProvider
     * @param LineItemSubtotalProvider $subtotalProvider
     */
    public function __construct(
        TotalProcessorProvider $totalProvider,
        LineItemSubtotalProvider $subtotalProvider
    ) {
        $this->totalProvider = $totalProvider;
        $this->subtotalProvider = $subtotalProvider;
    }

    /**
     * @param QuoteDemand $quoteDemand
     */
    public function recalculateSubtotals(QuoteDemand $quoteDemand)
    {
        $subtotal = $this->subtotalProvider->getSubtotal($quoteDemand);
        if ($subtotal) {
            $quoteDemand->setSubtotal($subtotal->getAmount());
        }

        $total = $this->totalProvider->getTotal($quoteDemand);
        if ($total) {
            $quoteDemand->setTotal($total->getAmount());
        }
        
        $quoteDemand->setTotalCurrency($subtotal->getCurrency());
    }
}
