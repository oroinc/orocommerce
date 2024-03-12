<?php

namespace Oro\Bundle\SaleBundle\Manager;

use Oro\Bundle\PricingBundle\SubtotalProcessor\Provider\LineItemSubtotalProvider;
use Oro\Bundle\PricingBundle\SubtotalProcessor\TotalProcessorProvider;
use Oro\Bundle\ProductBundle\LineItemChecksumGenerator\LineItemChecksumGeneratorInterface;
use Oro\Bundle\SaleBundle\Entity\QuoteDemand;

/**
 * Handles logic related to {@see QuoteDemand} and {@see QuoteProductDemand} manipulations.
 */
class QuoteDemandManager
{
    private TotalProcessorProvider $totalProvider;

    private LineItemSubtotalProvider $subtotalProvider;

    private LineItemChecksumGeneratorInterface $lineItemChecksumGenerator;

    public function __construct(
        TotalProcessorProvider $totalProvider,
        LineItemSubtotalProvider $subtotalProvider,
        LineItemChecksumGeneratorInterface $lineItemChecksumGenerator
    ) {
        $this->totalProvider = $totalProvider;
        $this->subtotalProvider = $subtotalProvider;
        $this->lineItemChecksumGenerator = $lineItemChecksumGenerator;
    }

    public function recalculateSubtotals(QuoteDemand $quoteDemand): void
    {
        $subtotal = $this->subtotalProvider->getSubtotal($quoteDemand);
        $quoteDemand->setSubtotal($subtotal->getAmount());

        $total = $this->totalProvider->getTotal($quoteDemand);
        $quoteDemand->setTotal($total->getAmount());

        $quoteDemand->setTotalCurrency($subtotal->getCurrency());
    }

    public function updateQuoteProductDemandChecksum(QuoteDemand $quoteDemand): void
    {
        foreach ($quoteDemand->getDemandProducts() as $quoteProductDemand) {
            $checksum = $this->lineItemChecksumGenerator->getChecksum($quoteProductDemand);
            $quoteProductDemand->setChecksum($checksum ?? '');
        }
    }
}
