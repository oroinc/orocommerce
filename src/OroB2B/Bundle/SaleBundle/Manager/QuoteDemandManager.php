<?php

namespace OroB2B\Bundle\SaleBundle\Manager;

use OroB2B\Bundle\PricingBundle\Provider\UserCurrencyProvider;
use OroB2B\Bundle\PricingBundle\SubtotalProcessor\Model\SubtotalProviderInterface;
use OroB2B\Bundle\PricingBundle\SubtotalProcessor\TotalProcessorProvider;
use OroB2B\Bundle\SaleBundle\Entity\QuoteDemand;

class QuoteDemandManager
{
    /**
     * @var TotalProcessorProvider
     */
    protected $totalProvider;

    /**
     * @var SubtotalProviderInterface
     */
    protected $subtotalProvider;

    /**
     * @var UserCurrencyProvider
     */
    protected $currencyProvider;

    /**
     * @param TotalProcessorProvider $totalProvider
     * @param SubtotalProviderInterface $subtotalProvider
     * @param UserCurrencyProvider $currencyProvider
     */
    public function __construct(
        TotalProcessorProvider $totalProvider,
        SubtotalProviderInterface $subtotalProvider,
        UserCurrencyProvider $currencyProvider
    ) {
        $this->totalProvider = $totalProvider;
        $this->subtotalProvider = $subtotalProvider;
        $this->currencyProvider = $currencyProvider;
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
        
        $quoteDemand->setTotalCurrency($this->currencyProvider->getUserCurrency());
    }
}
