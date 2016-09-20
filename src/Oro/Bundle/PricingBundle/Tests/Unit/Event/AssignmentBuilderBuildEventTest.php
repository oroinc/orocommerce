<?php

namespace Oro\Bundle\PricingBundle\Tests\Unit\Event;

use Oro\Bundle\PricingBundle\Entity\PriceList;
use Oro\Bundle\PricingBundle\Event\AssignmentBuilderBuildEvent;
use Oro\Bundle\ProductBundle\Entity\Product;

class AssignmentBuilderBuildEventTest extends \PHPUnit_Framework_TestCase
{
    public function testEvent()
    {
        $priceList = new PriceList();

        $event = new AssignmentBuilderBuildEvent($priceList);
        $this->assertSame($priceList, $event->getPriceList());
        $this->assertNull($event->getProduct());
    }

    public function testEventWithProduct()
    {
        $priceList = new PriceList();
        $product = new Product();

        $event = new AssignmentBuilderBuildEvent($priceList, $product);
        $this->assertSame($priceList, $event->getPriceList());
        $this->assertSame($product, $event->getProduct());
    }
}
