<?php

namespace Oro\Bundle\PaymentBundle\Tests\Unit\Context\Builder\Basic\Factory;

use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\PaymentBundle\Context\Builder\Basic\BasicPaymentContextBuilder;
use Oro\Bundle\PaymentBundle\Context\Builder\Basic\Factory\BasicPaymentContextBuilderFactory;
use Oro\Bundle\PaymentBundle\Context\LineItem\Collection\Factory\PaymentLineItemCollectionFactoryInterface;

class BasicPaymentContextBuilderFactoryTest extends \PHPUnit\Framework\TestCase
{
    /** @var Checkout|\PHPUnit\Framework\MockObject\MockObject */
    private $sourceEntity;

    /** @var PaymentLineItemCollectionFactoryInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $paymentLineItemCollectionFactory;

    protected function setUp(): void
    {
        $this->sourceEntity = $this->createMock(Checkout::class);
        $this->paymentLineItemCollectionFactory = $this->createMock(PaymentLineItemCollectionFactoryInterface::class);
    }

    public function testCreateBuilder()
    {
        $entityId = '12';

        $builderFactory = new BasicPaymentContextBuilderFactory(
            $this->paymentLineItemCollectionFactory
        );

        $builder = $builderFactory->createPaymentContextBuilder(
            $this->sourceEntity,
            $entityId
        );

        $expectedBuilder = new BasicPaymentContextBuilder(
            $this->sourceEntity,
            $entityId,
            $this->paymentLineItemCollectionFactory
        );

        self::assertEquals($expectedBuilder, $builder);
    }
}
