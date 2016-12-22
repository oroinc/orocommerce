<?php

namespace Oro\Bundle\ShippingBundle\Tests\Unit\Context\Builder\Basic\Factory;

use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\ShippingBundle\Context\Builder\Basic\BasicShippingContextBuilder;
use Oro\Bundle\ShippingBundle\Context\Builder\Basic\Factory\BasicShippingContextBuilderFactory;
use Oro\Bundle\ShippingBundle\Context\LineItem\Collection\Factory\ShippingLineItemCollectionFactoryInterface;
use Oro\Bundle\ShippingBundle\Context\LineItem\Collection\ShippingLineItemCollectionInterface;
use Oro\Bundle\ShippingBundle\Provider\ShippingOriginProvider;

class BasicShippingContextBuilderFactoryTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var ShippingLineItemCollectionInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $lineItemsCollectionMock;

    /**
     * @var Price|\PHPUnit_Framework_MockObject_MockObject
     */
    private $subtotalMock;

    /**
     * @var Checkout|\PHPUnit_Framework_MockObject_MockObject
     */
    private $sourceEntityMock;

    /**
     * @var ShippingLineItemCollectionFactoryInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $shippingLineItemCollectionFactoryMock;

    /**
     * @var ShippingOriginProvider|\PHPUnit_Framework_MockObject_MockObject
     */
    private $shippingOriginProviderMock;

    protected function setUp()
    {
        $this->lineItemsCollectionMock = $this->createMock(ShippingLineItemCollectionInterface::class);
        $this->subtotalMock = $this->getMockBuilder(Price::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->sourceEntityMock = $this->getMockBuilder(Checkout::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->shippingLineItemCollectionFactoryMock = $this->createMock(
            ShippingLineItemCollectionFactoryInterface::class
        );
        $this->shippingOriginProviderMock = $this->getMockBuilder(ShippingOriginProvider::class)
            ->disableOriginalConstructor()
            ->getMock();
    }

    public function testCreateBuilder()
    {
        $currency = 'usd';
        $entityId = '12';

        $this->shippingOriginProviderMock
            ->expects($this->never())
            ->method('getSystemShippingOrigin');

        $builderFactory = new BasicShippingContextBuilderFactory(
            $this->shippingLineItemCollectionFactoryMock,
            $this->shippingOriginProviderMock
        );

        $builder = $builderFactory->createShippingContextBuilder(
            $currency,
            $this->subtotalMock,
            $this->sourceEntityMock,
            $entityId
        );

        $expectedBuilder = new BasicShippingContextBuilder(
            $currency,
            $this->subtotalMock,
            $this->sourceEntityMock,
            $entityId,
            $this->shippingLineItemCollectionFactoryMock,
            $this->shippingOriginProviderMock
        );

        $this->assertEquals($expectedBuilder, $builder);
    }
}
