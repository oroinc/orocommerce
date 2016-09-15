<?php

namespace Oro\Bundle\OrderBundle\Tests\Unit\EventListener;

use Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\OrderBundle\Entity\OrderLineItem;
use Oro\Bundle\OrderBundle\EventListener\ExtractLineItemPaymentOptionsListener;
use Oro\Bundle\PaymentBundle\Event\ExtractLineItemPaymentOptionsEvent;
use Oro\Bundle\PaymentBundle\Model\LineItemOptionModel;
use Oro\Bundle\ProductBundle\Tests\Unit\Entity\Stub\Product;

class ExtractLineItemPaymentOptionsListenerTest extends \PHPUnit_Framework_TestCase
{
    /** @var ExtractLineItemPaymentOptionsListener */
    private $listener;

    public function setUp()
    {
        $this->listener = new ExtractLineItemPaymentOptionsListener();
    }

    public function tearDown()
    {
        unset($this->listener);
    }

    public function testOnExtractLineItemPaymentOptions()
    {
        $itemWithProduct = new OrderLineItem();

        $product = new Product();
        $product
            ->addName((new LocalizedFallbackValue())->setString('Product Name'))
            ->addShortDescription((new LocalizedFallbackValue())->setText('Product Description'));

        $itemWithProduct->setProduct($product);
        $itemWithProduct->setValue(123.456);
        $itemWithProduct->setQuantity(2);

        $entity = new Order();
        $entity->addLineItem($itemWithProduct);
        $entity->addLineItem(new OrderLineItem());

        $event = new ExtractLineItemPaymentOptionsEvent($entity);
        $this->listener->onExtractLineItemPaymentOptions($event);

        $models = $event->getModels();

        $this->assertInternalType('array', $models);
        $this->assertContainsOnlyInstancesOf(LineItemOptionModel::class, $models);

        /** @var LineItemOptionModel $model */
        $model = reset($models);

        $this->assertEquals('Product Name', $model->getName());
        $this->assertEquals('Product Description', $model->getDescription());
        $this->assertEquals('123.456', $model->getCost());
        $this->assertEquals('2', $model->getQty());
    }

    public function testOnExtractLineItemPaymentOptionsWithoutLineItems()
    {
        $entity = new Order();

        $event = new ExtractLineItemPaymentOptionsEvent($entity);
        $this->listener->onExtractLineItemPaymentOptions($event);

        $models = $event->getModels();

        $this->assertInternalType('array', $models);
        $this->assertEmpty($models);
        $this->assertContainsOnlyInstancesOf(LineItemOptionModel::class, $models);
    }
}
