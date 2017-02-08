<?php

namespace Oro\Bundle\SaleBundle\Quote\Shipping\LineItem\Converter\FirstOffers;

use Oro\Bundle\SaleBundle\Entity\Quote;
use Oro\Bundle\SaleBundle\Quote\Shipping\LineItem\Converter\QuoteToShippingLineItemConverterInterface;
use Oro\Bundle\ShippingBundle\Context\LineItem\Builder\Factory\ShippingLineItemBuilderFactoryInterface;
use Oro\Bundle\ShippingBundle\Context\LineItem\Collection\Factory\ShippingLineItemCollectionFactoryInterface;

class FirstOffersQuoteToShippingLineItemConverter implements QuoteToShippingLineItemConverterInterface
{
    /**
     * @var ShippingLineItemCollectionFactoryInterface
     */
    private $shippingLineItemCollectionFactory;

    /**
     * @var ShippingLineItemBuilderFactoryInterface
     */
    private $shippingLineItemBuilderFactory;

    /**
     * @param ShippingLineItemCollectionFactoryInterface $shippingLineItemCollectionFactory
     * @param ShippingLineItemBuilderFactoryInterface $shippingLineItemBuilderFactory
     */
    public function __construct(
        ShippingLineItemCollectionFactoryInterface $shippingLineItemCollectionFactory,
        ShippingLineItemBuilderFactoryInterface $shippingLineItemBuilderFactory
    ) {
        $this->shippingLineItemCollectionFactory = $shippingLineItemCollectionFactory;
        $this->shippingLineItemBuilderFactory = $shippingLineItemBuilderFactory;
    }

    /**
     * [@inheritdoc}
     */
    public function convertLineItems(Quote $quote)
    {
        $lineItems = [];

        foreach ($quote->getQuoteProducts() as $quoteProduct) {
            $offers = $quoteProduct->getQuoteProductOffers();
            if ($offers->count() <= 0) {
                $lineItems = [];
                break;
            }

            $firstQuoteProductOffer = $offers->first();
            if (!$firstQuoteProductOffer->getPrice()) {
                $lineItems = [];
                break;
            }

            $lineItemBuilder = $this->shippingLineItemBuilderFactory->createBuilder(
                $firstQuoteProductOffer->getPrice(),
                $firstQuoteProductOffer->getProductUnit(),
                $firstQuoteProductOffer->getProductUnitCode(),
                $firstQuoteProductOffer->getQuantity(),
                $firstQuoteProductOffer
            );

            if (null !== $firstQuoteProductOffer->getProduct()) {
                $lineItemBuilder->setProduct($firstQuoteProductOffer->getProduct());
            }

            $lineItems[] = $lineItemBuilder->getResult();
        }

        return $this->shippingLineItemCollectionFactory->createShippingLineItemCollection($lineItems);
    }
}
