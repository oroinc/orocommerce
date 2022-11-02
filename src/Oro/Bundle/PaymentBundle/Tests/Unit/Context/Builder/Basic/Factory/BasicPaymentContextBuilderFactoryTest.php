<?php

namespace Oro\Bundle\PaymentBundle\Tests\Unit\Context\Builder\Basic\Factory;

use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\PaymentBundle\Context\Builder\Basic\BasicPaymentContextBuilder;
use Oro\Bundle\PaymentBundle\Context\Builder\Basic\Factory\BasicPaymentContextBuilderFactory;
use Oro\Bundle\PaymentBundle\Context\LineItem\Collection\Factory\PaymentLineItemCollectionFactoryInterface;
use Oro\Bundle\PaymentBundle\Context\LineItem\Collection\PaymentLineItemCollectionInterface;

class BasicPaymentContextBuilderFactoryTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var PaymentLineItemCollectionInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private $lineItemsCollectionMock;

    /**
     * @var Price|\PHPUnit\Framework\MockObject\MockObject
     */
    private $subtotalMock;

    /**
     * @var Checkout|\PHPUnit\Framework\MockObject\MockObject
     */
    private $sourceEntityMock;

    /**
     * @var PaymentLineItemCollectionFactoryInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private $paymentLineItemCollectionFactoryMock;

    protected function setUp(): void
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
        $entityId = '12';

        $builderFactory = new BasicPaymentContextBuilderFactory(
            $this->paymentLineItemCollectionFactoryMock
        );

        $builder = $builderFactory->createPaymentContextBuilder(
            $this->sourceEntityMock,
            $entityId
        );

        $expectedBuilder = new BasicPaymentContextBuilder(
            $this->sourceEntityMock,
            $entityId,
            $this->paymentLineItemCollectionFactoryMock
        );

        static::assertEquals($expectedBuilder, $builder);
    }
}
