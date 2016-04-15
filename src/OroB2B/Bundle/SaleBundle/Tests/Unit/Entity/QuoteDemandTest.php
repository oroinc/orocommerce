<?php

namespace OroB2B\Bundle\SaleBundle\Tests\Unit\Entity;

use Oro\Bundle\CurrencyBundle\Entity\Price;

use Oro\Component\Testing\Unit\EntityTrait;
use OroB2B\Bundle\SaleBundle\Entity\Quote;
use OroB2B\Bundle\SaleBundle\Entity\QuoteDemand;
use OroB2B\Bundle\SaleBundle\Entity\QuoteProduct;
use OroB2B\Bundle\SaleBundle\Entity\QuoteProductDemand;
use OroB2B\Bundle\SaleBundle\Entity\QuoteProductOffer;

class QuoteDemandTest extends AbstractTest
{
    use EntityTrait;

    public function testProperties()
    {
        $properties = [
            ['id', '123'],
            ['quote', new Quote()],
            ['total', 100.1],
            ['subtotal', 100.1],
            ['totalCurrency', 'USD']
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

    public function testSourceDocument()
    {
        /** @var Quote $quote */
        $quote = $this->getEntity('OroB2B\Bundle\SaleBundle\Entity\Quote', ['id' => 1, 'poNumber' => 'PO123']);
        $demand = new QuoteDemand();
        $demand->setQuote($quote);

        $this->assertSame($quote, $demand->getSourceDocument());
        $this->assertEquals('PO123', $demand->getSourceDocumentIdentifier());
    }
}
