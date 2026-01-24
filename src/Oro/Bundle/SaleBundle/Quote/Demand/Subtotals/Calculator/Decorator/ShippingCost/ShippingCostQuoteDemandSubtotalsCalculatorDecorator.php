<?php

namespace Oro\Bundle\SaleBundle\Quote\Demand\Subtotals\Calculator\Decorator\ShippingCost;

use Oro\Bundle\SaleBundle\Entity\QuoteDemand;
use Oro\Bundle\SaleBundle\Quote\Demand\Subtotals\Calculator\Decorator\AbstractQuoteDemandSubtotalsCalculatorDecorator;
use Oro\Bundle\SaleBundle\Quote\Demand\Subtotals\Calculator\QuoteDemandSubtotalsCalculatorInterface;
use Oro\Bundle\SaleBundle\Quote\Shipping\Configuration\QuoteShippingConfigurationFactory;
use Oro\Bundle\ShippingBundle\Context\ShippingContextFactoryInterface;
use Oro\Bundle\ShippingBundle\Provider\Price\Configured\ShippingConfiguredPriceProviderInterface;

/**
 * Decorator that adds shipping cost calculation to quote demand subtotals.
 *
 * Wraps another {@see QuoteDemandSubtotalsCalculator} to augment its functionality by calculating and setting
 * the estimated shipping cost on the quote before delegating to the wrapped calculator for final subtotal computation.
 */
class ShippingCostQuoteDemandSubtotalsCalculatorDecorator extends AbstractQuoteDemandSubtotalsCalculatorDecorator
{
    /**
     * @var ShippingContextFactoryInterface
     */
    private $quoteShippingContextFactory;

    /**
     * @var QuoteShippingConfigurationFactory
     */
    private $quoteShippingConfigurationFactory;

    /**
     * @var ShippingConfiguredPriceProviderInterface
     */
    private $shippingConfiguredPriceProvider;

    public function __construct(
        ShippingContextFactoryInterface $quoteShippingContextFactory,
        QuoteShippingConfigurationFactory $quoteShippingConfigurationFactory,
        ShippingConfiguredPriceProviderInterface $shippingConfiguredPriceProvider,
        QuoteDemandSubtotalsCalculatorInterface $quoteDemandSubtotalsCalculator
    ) {
        $this->quoteShippingContextFactory = $quoteShippingContextFactory;
        $this->quoteShippingConfigurationFactory = $quoteShippingConfigurationFactory;
        $this->shippingConfiguredPriceProvider = $shippingConfiguredPriceProvider;

        parent::__construct($quoteDemandSubtotalsCalculator);
    }

    #[\Override]
    public function calculateSubtotals(QuoteDemand $quoteDemand)
    {
        $quote = $quoteDemand->getQuote();

        $shippingContext = $this->quoteShippingContextFactory->create($quote);
        $configuration = $this->quoteShippingConfigurationFactory->createQuoteShippingConfig($quote);

        $price = $this->shippingConfiguredPriceProvider->getPrice(
            $quote->getShippingMethod(),
            $quote->getShippingMethodType(),
            $configuration,
            $shippingContext
        );

        $shippingCostAmount = null;

        if (null !== $price) {
            $shippingCostAmount = $price->getValue();
        }

        $quote->setEstimatedShippingCostAmount($shippingCostAmount);

        return parent::calculateSubtotals($quoteDemand);
    }
}
