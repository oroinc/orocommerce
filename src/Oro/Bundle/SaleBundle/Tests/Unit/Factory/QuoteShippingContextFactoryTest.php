<?php

namespace Oro\Bundle\SaleBundle\Tests\Unit\EventListener;

use Oro\Bundle\SaleBundle\Entity\Quote;
use Oro\Bundle\SaleBundle\Entity\QuoteAddress;
use Oro\Bundle\SaleBundle\Entity\QuoteProduct;
use Oro\Bundle\SaleBundle\Factory\QuoteShippingContextFactory;
use Oro\Bundle\ShippingBundle\Context\ShippingContext;
use Oro\Bundle\ShippingBundle\Factory\ShippingContextFactory;

class QuoteShippingContextFactoryTest extends \PHPUnit_Framework_TestCase
{
    public function testCreateReturnsNullIfNoFactory()
    {
        $factory = new QuoteShippingContextFactory();
        $quote = new Quote();

        static::assertNull($factory->create($quote));
    }

    public function testCreateReturnRightEntity()
    {
        $factory = new QuoteShippingContextFactory();
        $factory->setShippingContextFactory($this->getTestShippingContextFactory());

        $shippingAddress = $this->getShippingAddressMock();
        $product = $this->getQuoteProductMock();

        $quote = new Quote();
        $quote->setShippingAddress($shippingAddress);
        $quote->addQuoteProduct($product);

        $shippingContext = $factory->create($quote);

        static::assertInstanceOf(ShippingContext::class, $shippingContext);
        static::assertSame($shippingAddress, $shippingContext->getShippingAddress());
        static::assertSame($quote, $shippingContext->getSourceEntity());
        static::assertCount(1, $shippingContext->getLineItems());
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|ShippingContextFactory
     */
    private function getTestShippingContextFactory()
    {
        $factory = $this->getMockBuilder('Oro\Bundle\ShippingBundle\Factory\ShippingContextFactory')
            ->disableOriginalConstructor()
            ->getMock();

        $factory->expects(static::any())
            ->method('create')
            ->willReturn($this->getShippingContextMock());

        return $factory;
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|ShippingContext
     */
    private function getShippingContextMock()
    {
        return $this->getMockBuilder('Oro\Bundle\ShippingBundle\Context\ShippingContext')
            ->disableOriginalConstructor()
            ->setMethods(null)
            ->getMock();
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|QuoteAddress
     */
    private function getShippingAddressMock()
    {
        return $this->getMockBuilder('Oro\Bundle\SaleBundle\Entity\QuoteAddress')
            ->disableOriginalConstructor()
            ->getMock();
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|QuoteProduct
     */
    private function getQuoteProductMock()
    {
        return $this->getMockBuilder('Oro\Bundle\SaleBundle\Entity\QuoteProduct')
            ->disableOriginalConstructor()
            ->getMock();
    }
}
