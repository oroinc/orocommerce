<?php

namespace Oro\Bundle\OrderBundle\Tests\Unit\EventListener;

use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\OrderBundle\Entity\OrderLineItem;
use Oro\Bundle\OrderBundle\EventListener\ExtractLineItemPaymentOptionsListener;
use Oro\Bundle\PaymentBundle\Event\ExtractLineItemPaymentOptionsEvent;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Model\ExtendProduct;

class ExtractLineItemPaymentOptionsListenerTest extends \PHPUnit_Framework_TestCase
{
    public function testOnExtractLineItemPaymentOptions()
    {
        $entity = new Order();
        $entity->addLineItem(new OrderLineItem());
        $itemWithProduct = new OrderLineItem();
        /** @var ExtendProduct|\PHPUnit_Framework_MockObject_MockObject */
        $productMock = $this->getMockBuilder(ExtendProduct::class)->disableOriginalConstructor()->getMock();
        $productMock->expects($this->once())->method('getDefaultName')->willReturn('Some name');
        $productMock->expects($this->once())->method('getDefaultShortDescription')
            ->willReturn('Some description');
        $itemWithProduct->setProduct($productMock);
        $itemWithProduct->setValue(123.456);
        $itemWithProduct->setQuantity(2.2);
        $entity->addLineItem($itemWithProduct);
        $keys = ['key1', 'key2', 'key3', 'key4'];
        $event = new ExtractLineItemPaymentOptionsEvent($entity, $keys);

        $listener = new ExtractLineItemPaymentOptionsListener();
        $listener->onExtractLineItemPaymentOptions($event);
    }
    /* public function onExtractLineItemPaymentOptions(ExtractLineItemPaymentOptionsEvent $event)
     {
         $entity = $event->getEntity();
         $lineItems = $entity->getLineItems();
         $options = [];
         foreach ($lineItems as $lineItem) {
             if (!$lineItem instanceof OrderLineItem) {
                 continue;
             }

             $product = $lineItem->getProduct();

             if (!$product) {
                 continue;
             }

             $lineItemOptions = [
                 (string)$product->getDefaultName(),
                 (string)$product->getDefaultShortDescription(),
                 $lineItem->getValue(),
                 (int)$lineItem->getQuantity()
             ];

             $options[] = $event->applyKeys($lineItemOptions);
         }

         $event->setOptions($options);
     }*/
}
