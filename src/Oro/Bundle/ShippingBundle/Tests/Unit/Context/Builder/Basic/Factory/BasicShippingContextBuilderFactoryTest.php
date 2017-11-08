<?php

namespace Oro\Bundle\ShippingBundle\Tests\Unit\Context\Builder\Basic\Factory;

use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\ShippingBundle\Context\Builder\Basic\BasicShippingContextBuilder;
use Oro\Bundle\ShippingBundle\Context\Builder\Basic\Factory\BasicShippingContextBuilderFactory;
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
     * @var ShippingOriginProvider|\PHPUnit_Framework_MockObject_MockObject
     */
    private $shippingOriginProviderMock;

    protected function setUp()
    {
        $this->lineItemsCollectionMock = $this->createMock(ShippingLineItemCollectionInterface::class);
        $this->subtotalMock = $this->createMock(Price::class);
        $this->sourceEntityMock = $this->createMock(Checkout::class);
        $this->shippingOriginProviderMock = $this->createMock(ShippingOriginProvider::class);
    }

    public function testCreateBuilder()
    {
        $entityId = '12';

        $this->shippingOriginProviderMock
            ->expects($this->never())
            ->method('getSystemShippingOrigin');

        $builderFactory = new BasicShippingContextBuilderFactory(
            $this->shippingOriginProviderMock
        );

        $builder = $builderFactory->createShippingContextBuilder(
            $this->sourceEntityMock,
            $entityId
        );

        $expectedBuilder = new BasicShippingContextBuilder(
            $this->sourceEntityMock,
            $entityId,
            $this->shippingOriginProviderMock
        );

        $this->assertEquals($expectedBuilder, $builder);
    }
}
