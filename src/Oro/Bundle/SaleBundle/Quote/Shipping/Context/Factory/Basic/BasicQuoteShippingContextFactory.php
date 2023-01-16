<?php

namespace Oro\Bundle\SaleBundle\Quote\Shipping\Context\Factory\Basic;

use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\PricingBundle\SubtotalProcessor\TotalProcessorProvider;
use Oro\Bundle\SaleBundle\Entity\Quote;
use Oro\Bundle\SaleBundle\Quote\Calculable\Factory\CalculableQuoteFactoryInterface;
use Oro\Bundle\SaleBundle\Quote\Shipping\LineItem\Converter\QuoteToShippingLineItemConverterInterface;
use Oro\Bundle\ShippingBundle\Context\Builder\Factory\ShippingContextBuilderFactoryInterface;
use Oro\Bundle\ShippingBundle\Context\ShippingContextFactoryInterface;
use Oro\Bundle\ShippingBundle\Context\ShippingContextInterface;

/**
 * Creates a shipping context based on a quote entity.
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
    public function create(object $entity): ShippingContextInterface
    {
        if (!$entity instanceof Quote) {
            throw new \InvalidArgumentException(sprintf(
                '"%s" expected, "%s" given',
                Quote::class,
                get_debug_type($entity)
            ));
        }

        $this->totalProcessorProvider->enableRecalculation();

        $shippingContextBuilder = $this->shippingContextBuilderFactory->createShippingContextBuilder(
            $entity,
            $entity->getId()
        );

        $convertedLineItems = $this->quoteToShippingLineItemConverter->convertLineItems($entity);
        $calculableQuote = $this->calculableQuoteFactory->createCalculableQuote($convertedLineItems);
        $total = $this->totalProcessorProvider->getTotal($calculableQuote);
        $subtotal = Price::create($total->getAmount(), $total->getCurrency());

        $shippingContextBuilder
            ->setSubTotal($subtotal)
            ->setCurrency($entity->getCurrency())
            ->setLineItems($convertedLineItems);

        if ($entity->getCustomer()) {
            $shippingContextBuilder->setCustomer($entity->getCustomer());
        }

        if ($entity->getCustomerUser()) {
            $shippingContextBuilder->setCustomerUser($entity->getCustomerUser());
        }

        if (null !== $entity->getWebsite()) {
            $shippingContextBuilder->setWebsite($entity->getWebsite());
        }

        if (null !== $entity->getShippingAddress()) {
            $shippingContextBuilder->setShippingAddress($entity->getShippingAddress());
        }

        return $shippingContextBuilder->getResult();
    }
}
