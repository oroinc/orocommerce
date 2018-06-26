<?php

namespace Oro\Bundle\ShippingBundle\Tests\Unit\Context\LineItem\Collection\ArrayCollectionDoctrine;

use Oro\Bundle\ShippingBundle\Context\LineItem\Collection\Doctrine\DoctrineShippingLineItemCollection;
use Oro\Bundle\ShippingBundle\Context\ShippingLineItem;

class DoctrineShippingLineItemCollectionTest extends \PHPUnit\Framework\TestCase
{
    public function testCollection()
    {
        $shippingLineItems = [
            new ShippingLineItem([]),
            new ShippingLineItem([]),
            new ShippingLineItem([]),
            new ShippingLineItem([]),
        ];

        $collection = new DoctrineShippingLineItemCollection($shippingLineItems);

        $this->assertEquals($shippingLineItems, $collection->toArray());
    }
}
