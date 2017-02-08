<?php

namespace Oro\Bundle\SaleBundle\Quote\Shipping\Context\Factory\Basic;

use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\PricingBundle\SubtotalProcessor\TotalProcessorProvider;
use Oro\Bundle\SaleBundle\Entity\Quote;
use Oro\Bundle\SaleBundle\Quote\Calculable\Factory\CalculableQuoteFactoryInterface;
use Oro\Bundle\SaleBundle\Quote\Shipping\LineItem\Converter\QuoteToShippingLineItemConverterInterface;
use Oro\Bundle\ShippingBundle\Context\ShippingContextFactoryInterface;
use Oro\Bundle\ShippingBundle\Context\Builder\Factory\ShippingContextBuilderFactoryInterface;

class BasicQuoteShippingContextFactory implements ShippingContextFactoryInterface
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
     * @var CalculableQuoteFactoryInterface
     */
    private $calculableQuoteFactory;

    /**
     * @param ShippingContextBuilderFactoryInterface $shippingContextBuilderFactory
     * @param QuoteToShippingLineItemConverterInterface $quoteToShippingLineItemConverter
     * @param TotalProcessorProvider $totalProcessorProvider
     * @param CalculableQuoteFactoryInterface $calculableQuoteFactory
     */
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
     * {@inheritdoc}
     * @param Quote $quote
     */
    public function create($quote)
    {
        $this->ensureApplicable($quote);

        $this->totalProcessorProvider->enableRecalculation();

        $convertedLineItems = $this->quoteToShippingLineItemConverter->convertLineItems($quote);

        $calculableQuote = $this->calculableQuoteFactory->createCalculableQuote($convertedLineItems);
        $total = $this->totalProcessorProvider->getTotal($calculableQuote);
        $subtotal = Price::create($total->getAmount(), $total->getCurrency());

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

    /**
     * @param object $entity
     * @throws \InvalidArgumentException
     */
    protected function ensureApplicable($entity)
    {
        if (!is_a($entity, Quote::class)) {
            throw new \InvalidArgumentException(sprintf(
                '"%s" expected, "%s" given',
                Quote::class,
                is_object($entity) ? get_class($entity) : gettype($entity)
            ));
        }
    }
}
