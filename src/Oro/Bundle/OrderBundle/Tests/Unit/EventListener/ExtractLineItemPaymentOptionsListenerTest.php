<?php

namespace Oro\Bundle\OrderBundle\Tests\Unit\EventListener;

use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\OrderBundle\Entity\OrderLineItem;
use Oro\Bundle\OrderBundle\EventListener\ExtractLineItemPaymentOptionsListener;
use Oro\Bundle\PaymentBundle\Event\ExtractLineItemPaymentOptionsEvent;
use Oro\Bundle\ProductBundle\Tests\Unit\Entity\Stub\Product;

class ExtractLineItemPaymentOptionsListenerTest extends \PHPUnit_Framework_TestCase
{
    public function testOnExtractLineItemPaymentOptions()
    {
        $entity = new Order();
        $entity->addLineItem(new OrderLineItem());
        $itemWithProduct = new OrderLineItem();
        $product = new Product();
        $itemWithProduct->setProduct($product);
        $itemWithProduct->setValue(123.456);
        $itemWithProduct->setQuantity(2);
        $entity->addLineItem($itemWithProduct);
        $expected = [
            'key1' => '',
            'key2' => '',
            'key3' => 123.456,
            'key4' => 2
        ];
        $event = new ExtractLineItemPaymentOptionsEvent($entity, array_keys($expected));
        $listener = new ExtractLineItemPaymentOptionsListener();
        $listener->onExtractLineItemPaymentOptions($event);
        $options = $event->getOptions();

        $this->assertEquals([$expected], $options);
        return;
    }
}
