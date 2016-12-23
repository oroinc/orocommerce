<?php

namespace Oro\Bundle\PaymentBundle\Tests\Unit\Context\Builder\Basic\Factory;

use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\PaymentBundle\Context\Builder\Basic\BasicPaymentContextBuilder;
use Oro\Bundle\PaymentBundle\Context\Builder\Basic\Factory\BasicPaymentContextBuilderFactory;
use Oro\Bundle\PaymentBundle\Context\LineItem\Collection\Factory\PaymentLineItemCollectionFactoryInterface;
use Oro\Bundle\PaymentBundle\Context\LineItem\Collection\PaymentLineItemCollectionInterface;

class BasicPaymentContextBuilderFactoryTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var PaymentLineItemCollectionInterface|\PHPUnit_Framework_MockObject_MockObject
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
     * @var PaymentLineItemCollectionFactoryInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $paymentLineItemCollectionFactoryMock;

    protected function setUp()
    {
        $this->lineItemsCollectionMock = $this->createMock(PaymentLineItemCollectionInterface::class);
        $this->subtotalMock = $this->getMockBuilder(Price::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->sourceEntityMock = $this->getMockBuilder(Checkout::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->paymentLineItemCollectionFactoryMock = $this->createMock(
            PaymentLineItemCollectionFactoryInterface::class
        );
    }

    public function testCreateBuilder()
    {
        $currency = 'usd';
        $entityId = '12';

        $builderFactory = new BasicPaymentContextBuilderFactory(
            $this->paymentLineItemCollectionFactoryMock
        );

        $builder = $builderFactory->createPaymentContextBuilder(
            $currency,
            $this->subtotalMock,
            $this->sourceEntityMock,
            $entityId
        );

        $expectedBuilder = new BasicPaymentContextBuilder(
            $currency,
            $this->subtotalMock,
            $this->sourceEntityMock,
            $entityId,
            $this->paymentLineItemCollectionFactoryMock
        );

        static::assertEquals($expectedBuilder, $builder);
    }
}
