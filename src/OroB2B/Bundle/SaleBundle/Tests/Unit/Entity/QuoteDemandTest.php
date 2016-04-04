<?php

namespace OroB2B\Bundle\SaleBundle\Tests\Unit\Entity;

use Oro\Bundle\CurrencyBundle\Entity\Price;

use OroB2B\Bundle\SaleBundle\Entity\Quote;
use OroB2B\Bundle\SaleBundle\Entity\QuoteDemand;
use OroB2B\Bundle\SaleBundle\Entity\QuoteProduct;
use OroB2B\Bundle\SaleBundle\Entity\QuoteProductDemand;
use OroB2B\Bundle\SaleBundle\Entity\QuoteProductOffer;

class QuoteDemandTest extends AbstractTest
{
    public function testProperties()
    {
        $properties = [
            ['id', '123'],
            ['quote', new Quote()]
        ];

        static::assertPropertyAccessors(new QuoteDemand(), $properties);
    }

    public function testSetQuantity()
    {
        $quote = new Quote();
        $quote->setShippingEstimate(Price::create(5, 'USD'));
        $quoteProduct = new QuoteProduct();
        $firstOffer = new QuoteProductOffer();
        $quoteProduct->addQuoteProductOffer($firstOffer);
        $quoteProduct->addQuoteProductOffer(new QuoteProductOffer());
        $quote->addQuoteProduct($quoteProduct);

        $demand = new QuoteDemand();
        $this->assertNull($demand->getShippingCost());
        $demand->setQuote($quote);

        /** @var QuoteProductDemand $firstDemandProduct */
        $firstDemandProduct = $demand->getDemandProducts()->first();
        $this->assertSame($firstOffer, $firstDemandProduct->getQuoteProductOffer());
        $this->assertSame($demand->getLineItems(), $demand->getDemandProducts());
        $demand->removeDemandProduct($firstDemandProduct);
        $this->assertNotContains($firstDemandProduct, $demand->getDemandProducts());
        $this->assertSame($quote->getShippingEstimate(), $demand->getShippingCost());
    }
}
