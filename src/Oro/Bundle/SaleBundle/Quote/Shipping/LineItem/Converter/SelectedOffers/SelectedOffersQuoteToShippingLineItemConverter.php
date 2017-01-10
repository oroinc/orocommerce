<?php

namespace Oro\Bundle\SaleBundle\Quote\Shipping\LineItem\Converter\SelectedOffers;

use Oro\Bundle\SaleBundle\Entity\Quote;
use Oro\Bundle\SaleBundle\Quote\Shipping\LineItem\Converter\QuoteToShippingLineItemConverterInterface;
use Oro\Bundle\ShippingBundle\Context\LineItem\Builder\Factory\ShippingLineItemBuilderFactoryInterface;
use Oro\Bundle\ShippingBundle\Context\LineItem\Collection\Factory\ShippingLineItemCollectionFactoryInterface;

class SelectedOffersQuoteToShippingLineItemConverter implements QuoteToShippingLineItemConverterInterface
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
     * {@inheritdoc}
     */
    public function convertLineItems(Quote $quote)
    {
        $shippingLineItems = [];

        foreach ($quote->getDemands() as $demand) {
            foreach ($demand->getDemandProducts() as $productDemand) {
                $productOffer = $productDemand->getQuoteProductOffer();

                $lineItemBuilder = $this->shippingLineItemBuilderFactory->createBuilder(
                    $productOffer->getPrice(),
                    $productOffer->getProductUnit(),
                    $productOffer->getProductUnitCode(),
                    $productDemand->getQuantity(),
                    $productOffer
                );

                $lineItemBuilder->setProduct($productOffer->getProduct());

                $shippingLineItems[] = $lineItemBuilder->getResult();
            }
        }

        return $this->shippingLineItemCollectionFactory->createShippingLineItemCollection($shippingLineItems);
    }
}
