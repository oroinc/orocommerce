<?php

namespace Oro\Bundle\SaleBundle\Tests\Unit\Entity;

use Oro\Bundle\CustomerBundle\Entity\Customer;
use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Oro\Bundle\CustomerBundle\Entity\CustomerVisitor;
use Oro\Bundle\SaleBundle\Entity\Quote;
use Oro\Bundle\SaleBundle\Entity\QuoteDemand;
use Oro\Bundle\SaleBundle\Entity\QuoteProduct;
use Oro\Bundle\SaleBundle\Entity\QuoteProductDemand;
use Oro\Bundle\SaleBundle\Entity\QuoteProductOffer;
use Oro\Component\Testing\ReflectionUtil;
use Oro\Component\Testing\Unit\EntityTestCaseTrait;

class QuoteDemandTest extends \PHPUnit\Framework\TestCase
{
    use EntityTestCaseTrait;

    public function testProperties()
    {
        $properties = [
            ['id', '123'],
            ['customer', new Customer()],
            ['customerUser', new CustomerUser()],
            ['visitor', new CustomerVisitor()],
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
        $quote->setCurrency('USD');
        $quote->setEstimatedShippingCostAmount(5);
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
        $this->assertEquals($quote->getShippingCost(), $demand->getShippingCost());
    }

    public function testSourceDocument()
    {
        $quote = new Quote();
        ReflectionUtil::setId($quote, 1);
        $quote->setPoNumber('PO123');
        $demand = new QuoteDemand();
        $demand->setQuote($quote);

        $this->assertSame($quote, $demand->getSourceDocument());
        $this->assertEquals('PO123', $demand->getSourceDocumentIdentifier());
    }

    public function testGetShippingMethod()
    {
        $quote = new Quote();
        $demand = new QuoteDemand();
        $demand->setQuote($quote);
        $this->assertNull($demand->getShippingMethod());
        $quote->setShippingMethod('test_ship');
        $this->assertSame('test_ship', $demand->getShippingMethod());
    }

    public function testGetShippingMethodType()
    {
        $quote = new Quote();
        $demand = new QuoteDemand();
        $demand->setQuote($quote);
        $this->assertNull($demand->getShippingMethodType());
        $quote->setShippingMethodType('test_ship_type');
        $this->assertSame('test_ship_type', $demand->getShippingMethodType());
    }
}
