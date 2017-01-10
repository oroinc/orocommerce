<?php

namespace Oro\Bundle\SaleBundle\Quote\Shipping\Context\Factory\Basic;

use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\PricingBundle\SubtotalProcessor\TotalProcessorProvider;
use Oro\Bundle\SaleBundle\Entity\Quote;
use Oro\Bundle\SaleBundle\Quote\Shipping\Context\Factory\QuoteShippingContextFactoryInterface;
use Oro\Bundle\SaleBundle\Quote\Shipping\LineItem\Converter\QuoteToShippingLineItemConverterInterface;
use Oro\Bundle\ShippingBundle\Context\Builder\Factory\ShippingContextBuilderFactoryInterface;

class BasicQuoteShippingContextFactory implements QuoteShippingContextFactoryInterface
{
    /**
     * @var ShippingContextBuilderFactoryInterface
     */
    private $shippingContextBuilderFactory;

    /**
     * @var QuoteToShippingLineItemConverterInterface
     */
    private $quoteToShippingLineItemConverter;

    /**
     * @var TotalProcessorProvider
     */
    private $totalProcessorProvider;

    /**
     * @param ShippingContextBuilderFactoryInterface $shippingContextBuilderFactory
     * @param QuoteToShippingLineItemConverterInterface $quoteToShippingLineItemConverter
     * @param TotalProcessorProvider $totalProcessorProvider
     */
    public function __construct(
        ShippingContextBuilderFactoryInterface $shippingContextBuilderFactory,
        QuoteToShippingLineItemConverterInterface $quoteToShippingLineItemConverter,
        TotalProcessorProvider $totalProcessorProvider
    ) {
        $this->shippingContextBuilderFactory = $shippingContextBuilderFactory;
        $this->quoteToShippingLineItemConverter = $quoteToShippingLineItemConverter;
        $this->totalProcessorProvider = $totalProcessorProvider;
    }

    /**
     * {@inheritdoc}
     */
    public function create(Quote $quote)
    {
        $convertedLineItems = $this->quoteToShippingLineItemConverter->convertLineItems($quote);

        //$total = $this->totalProcessorProvider->getTotal($quote);
        $subtotal = Price::create(
            0,
            ''
        );
        //@TODO: count by "shippingLineItemAwareInterface"

        $shippingContextBuilder = $this->shippingContextBuilderFactory->createShippingContextBuilder(
            $quote->getCurrency(),
            $subtotal,
            $quote,
            $quote->getId()
        );

        if (null !== $quote->getShippingAddress()) {
            $shippingContextBuilder
                ->setShippingAddress($quote->getShippingAddress());
        }

        if (false === $convertedLineItems->isEmpty()) {
            $shippingContextBuilder->setLineItems($convertedLineItems);
        }

        return $shippingContextBuilder->getResult();
    }
}
