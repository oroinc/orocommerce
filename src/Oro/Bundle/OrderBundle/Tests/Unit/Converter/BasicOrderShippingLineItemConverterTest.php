<?php

namespace Oro\Bundle\OrderBundle\Tests\Unit\Converter;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\OrderBundle\Converter\BasicOrderShippingLineItemConverter;
use Oro\Bundle\OrderBundle\Entity\OrderLineItem;
use Oro\Bundle\ShippingBundle\Context\LineItem\Collection\Doctrine\DoctrineShippingLineItemCollection;
use Oro\Bundle\ShippingBundle\Context\LineItem\Collection\Doctrine\Factory\DoctrineShippingLineItemCollectionFactory;
use Oro\Bundle\ShippingBundle\Context\ShippingLineItem;

class BasicOrderShippingLineItemConverterTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var DoctrineShippingLineItemCollectionFactory
     */
    private $collectionFactory;

    /**
     * @var BasicOrderShippingLineItemConverter
     */
    private $orderShippingLineItemConverter;

    public function setUp()
    {
        $this->collectionFactory = new DoctrineShippingLineItemCollectionFactory();
        $this->orderShippingLineItemConverter = new BasicOrderShippingLineItemConverter($this->collectionFactory);
    }

    public function testConvertLineItems()
    {
        $lineItemsData = [
            ['quantity' => 12],
            ['quantity' => 5],
            ['quantity' => 1],
            ['quantity' => 3],
            ['quantity' => 50],
        ];

        $orderLineItems = [];
        foreach ($lineItemsData as $lineItemData) {
            $orderLineItems[] = (new OrderLineItem())->setQuantity($lineItemData['quantity']);
        }

        $orderCollection = new ArrayCollection($orderLineItems);

        $shippingLineItemCollection = $this->orderShippingLineItemConverter->convertLineItems($orderCollection);

        $shippingLineItems = [];

        foreach ($orderLineItems as $orderLineItem) {
            $shippingLineItems[] = (new ShippingLineItem())
                ->setQuantity($orderLineItem->getQuantity())
                ->setProductHolder($orderLineItem);
        }

        $expectedLineItemCollection = new DoctrineShippingLineItemCollection($shippingLineItems);

        $this->assertEquals($expectedLineItemCollection, $shippingLineItemCollection);
    }
}
