<?php

namespace Oro\Bundle\SaleBundle\Quote\Shipping\Context\Factory\Basic;

use Oro\Bundle\SaleBundle\Entity\Quote;
use Oro\Bundle\SaleBundle\Quote\Shipping\Context\Factory\QuoteShippingContextFactoryInterface;
use Oro\Bundle\SaleBundle\Quote\Shipping\LineItem\QuoteToShippingLineItemConverterInterface;
use Oro\Bundle\ShippingBundle\Factory\ShippingContextFactory;

class BasicQuoteShippingContextFactory implements QuoteShippingContextFactoryInterface
{
    /**
     * @var ShippingContextFactory
     */
    private $shippingContextFactory;

    /**
     * @var QuoteToShippingLineItemConverterInterface
     */
    private $quoteToShippingLineItemConverter;

    /**
     * @param ShippingContextFactory $shippingContextFactory
     * @param QuoteToShippingLineItemConverterInterface $quoteToShippingLineItemConverter
     */
    public function __construct(
        ShippingContextFactory $shippingContextFactory,
        QuoteToShippingLineItemConverterInterface $quoteToShippingLineItemConverter
    ) {
        $this->shippingContextFactory = $shippingContextFactory;
        $this->quoteToShippingLineItemConverter = $quoteToShippingLineItemConverter;
    }

    /**
     * {@inheritdoc}
     */
    public function create(Quote $quote)
    {
        $shippingContext = $this->shippingContextFactory->create();

        $shippingContext->setShippingAddress($quote->getShippingAddress());
        $shippingContext->setCurrency($quote->getCurrency());
        $shippingContext->setSourceEntity($quote);
        $shippingContext->setSourceEntityIdentifier($quote->getId());

        if (!$quote->getDemands()->isEmpty()) {
            $shippingContext->setLineItemsByData(
                $this->quoteToShippingLineItemConverter->convertLineItems($quote)
            );
        }

        return $shippingContext;
    }
}
