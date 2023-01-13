<?php

namespace Oro\Bundle\SaleBundle\Quote\Shipping\Context\Factory\Basic;

use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\PricingBundle\SubtotalProcessor\TotalProcessorProvider;
use Oro\Bundle\SaleBundle\Entity\Quote;
use Oro\Bundle\SaleBundle\Quote\Calculable\Factory\CalculableQuoteFactoryInterface;
use Oro\Bundle\SaleBundle\Quote\Shipping\LineItem\Converter\QuoteToShippingLineItemConverterInterface;
use Oro\Bundle\ShippingBundle\Context\Builder\Factory\ShippingContextBuilderFactoryInterface;
use Oro\Bundle\ShippingBundle\Context\ShippingContextFactoryInterface;

/**
 * Creates shipping context based on a quote entity.
 */
class BasicQuoteShippingContextFactory implements ShippingContextFactoryInterface
{
    private ShippingContextBuilderFactoryInterface $shippingContextBuilderFactory;
    private QuoteToShippingLineItemConverterInterface $quoteToShippingLineItemConverter;
    private TotalProcessorProvider $totalProcessorProvider;
    private CalculableQuoteFactoryInterface $calculableQuoteFactory;

    public function __construct(
        ShippingContextBuilderFactoryInterface $shippingContextBuilderFactory,
        QuoteToShippingLineItemConverterInterface $quoteToShippingLineItemConverter,
        TotalProcessorProvider $totalProcessorProvider,
        CalculableQuoteFactoryInterface $calculableQuoteFactory
    ) {
        $this->shippingContextBuilderFactory = $shippingContextBuilderFactory;
        $this->quoteToShippingLineItemConverter = $quoteToShippingLineItemConverter;
        $this->totalProcessorProvider = $totalProcessorProvider;
        $this->calculableQuoteFactory = $calculableQuoteFactory;
    }

    /**
     * {@inheritDoc}
     */
    public function create($quote)
    {
        if (!$quote instanceof Quote) {
            throw new \InvalidArgumentException(sprintf(
                '"%s" expected, "%s" given',
                Quote::class,
                get_debug_type($quote)
            ));
        }

        $this->totalProcessorProvider->enableRecalculation();

        $shippingContextBuilder = $this->shippingContextBuilderFactory->createShippingContextBuilder(
            $quote,
            $quote->getId()
        );

        $convertedLineItems = $this->quoteToShippingLineItemConverter->convertLineItems($quote);
        $calculableQuote = $this->calculableQuoteFactory->createCalculableQuote($convertedLineItems);
        $total = $this->totalProcessorProvider->getTotal($calculableQuote);
        $subtotal = Price::create($total->getAmount(), $total->getCurrency());

        $shippingContextBuilder
            ->setSubTotal($subtotal)
            ->setCurrency($quote->getCurrency())
            ->setLineItems($convertedLineItems);

        if ($quote->getCustomer()) {
            $shippingContextBuilder->setCustomer($quote->getCustomer());
        }

        if ($quote->getCustomerUser()) {
            $shippingContextBuilder->setCustomerUser($quote->getCustomerUser());
        }

        if (null !== $quote->getWebsite()) {
            $shippingContextBuilder
                ->setWebsite($quote->getWebsite());
        }

        if (null !== $quote->getShippingAddress()) {
            $shippingContextBuilder
                ->setShippingAddress($quote->getShippingAddress());
        }

        return $shippingContextBuilder->getResult();
    }
}
