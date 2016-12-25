<?php

namespace Oro\Bundle\PaymentBundle\Tests\Unit\Context\LineItem\Collection\Doctrine\Factory;

use Oro\Bundle\PaymentBundle\Context\LineItem\Collection\Doctrine\Factory\DoctrinePaymentLineItemCollectionFactory;
use Oro\Bundle\PaymentBundle\Context\PaymentLineItem;
use Oro\Bundle\ShoppingListBundle\Entity\LineItem;

class DoctrinePaymentLineItemCollectionFactoryTest extends \PHPUnit_Framework_TestCase
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

    /**
     * @expectedException        \InvalidArgumentException
     * @expectedExceptionMessage Expected: Oro\Bundle\PaymentBundle\Context\PaymentLineItemInterface
     */
    public function testFactoryWithException()
    {
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
