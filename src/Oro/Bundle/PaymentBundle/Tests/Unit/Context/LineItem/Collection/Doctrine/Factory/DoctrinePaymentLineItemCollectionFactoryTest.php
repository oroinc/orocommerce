<?php

namespace Oro\Bundle\PaymentBundle\Tests\Unit\Context\LineItem\Collection\Doctrine\Factory;

use Oro\Bundle\PaymentBundle\Context\LineItem\Collection\Doctrine\Factory\DoctrinePaymentLineItemCollectionFactory;
use Oro\Bundle\PaymentBundle\Context\PaymentLineItem;
use Oro\Bundle\ShoppingListBundle\Entity\LineItem;

class DoctrinePaymentLineItemCollectionFactoryTest extends \PHPUnit\Framework\TestCase
{
    public function testFactory()
    {
        $paymentLineItems = [
            new PaymentLineItem([]),
            new PaymentLineItem([]),
            new PaymentLineItem([]),
            new PaymentLineItem([]),
        ];

        $collectionFactory = new DoctrinePaymentLineItemCollectionFactory();
        $collection = $collectionFactory->createPaymentLineItemCollection($paymentLineItems);

        static::assertEquals($paymentLineItems, $collection->toArray());
    }

    public function testFactoryWithException()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Expected: Oro\Bundle\PaymentBundle\Context\PaymentLineItemInterface');

        $lineItems = [
            new LineItem(),
            new LineItem(),
            new LineItem(),
            new LineItem(),
        ];

        $collectionFactory = new DoctrinePaymentLineItemCollectionFactory();
        $collectionFactory->createPaymentLineItemCollection($lineItems);
    }
}
