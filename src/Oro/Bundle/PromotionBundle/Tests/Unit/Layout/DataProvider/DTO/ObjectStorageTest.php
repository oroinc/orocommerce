<?php

namespace Oro\Bundle\PromotionBundle\Tests\Unit\Layout\DataProvider\DTO;

use Oro\Bundle\OrderBundle\Entity\OrderLineItem;
use Oro\Bundle\PromotionBundle\Layout\DataProvider\DTO\ObjectStorage;
use Oro\Bundle\PromotionBundle\Layout\DataProvider\DTO\UnsupportedObjectException;
use Oro\Component\Testing\Unit\EntityTrait;

class ObjectStorageTest extends \PHPUnit\Framework\TestCase
{
    use EntityTrait;

    public function testStorageObjectWithoutId()
    {
        $lineItem = new OrderLineItem();
        $lineItem->setProductSku('test');
        $data = ['test_data' => true];

        $storage = new ObjectStorage();
        $this->assertFalse($storage->contains($lineItem));
        $storage->attach($lineItem, $data);
        $this->assertTrue($storage->contains($lineItem));
        $this->assertSame($data, $storage->get($lineItem));
        $storage->detach($lineItem);
        $this->assertFalse($storage->contains($lineItem));
    }

    public function testStorageObjectId()
    {
        $lineItem = $this->getEntity(OrderLineItem::class, ['id' => 5]);
        $lineItem->setProductSku('test');
        $data = ['test_data' => true];

        $storage = new ObjectStorage();
        $this->assertFalse($storage->contains($lineItem));
        $storage->attach($lineItem, $data);
        $this->assertTrue($storage->contains($lineItem));
    }

    public function testStorageForObjectsWithSameData()
    {
        $lineItem = new OrderLineItem();
        $lineItem->setProductSku('test');

        $sameLineItem = new OrderLineItem();
        $sameLineItem->setProductSku('test');
        $data = ['test_data' => true];

        $storage = new ObjectStorage();
        $this->assertFalse($storage->contains($lineItem));
        $storage->attach($lineItem, $data);
        $this->assertTrue($storage->contains($lineItem));
        $this->assertTrue($storage->contains($sameLineItem));
    }

    public function testUnsupportedObjectException()
    {
        $lineItem = new \stdClass();
        $storage = new ObjectStorage();
        $this->expectException(UnsupportedObjectException::class);
        $storage->contains($lineItem);
    }
}
