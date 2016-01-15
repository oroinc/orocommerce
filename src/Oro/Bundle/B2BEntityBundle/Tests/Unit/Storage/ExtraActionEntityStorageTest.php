<?php

namespace OroB2B\src\Oro\Bundle\B2BEntityBundle\Tests\Unit\Storage;

use Oro\Bundle\B2BEntityBundle\Storage\ExtraActionEntityStorage;

class ExtraActionEntityStorageTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var ExtraActionEntityStorage
     */
    protected $storage;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->storage = new ExtraActionEntityStorage();
    }

    public function testScheduleForExtraInsert()
    {
        $object = $this->createTestObject();

        $this->storage->scheduleForExtraInsert($object);
        $this->assertSame([$object], $this->storage->getScheduledForInsert());
    }

    public function testHasScheduledForExtraInsert()
    {
        $object = $this->createTestObject();

        $this->assertFalse($this->storage->hasScheduledForInsert());

        $this->storage->scheduleForExtraInsert($object);
        $this->assertTrue($this->storage->hasScheduledForInsert());
    }

    public function testClearScheduledForInsert()
    {
        $this->storage->scheduleForExtraInsert($this->createTestObject());
        $this->storage->clearScheduledForInsert();
        $this->assertFalse($this->storage->hasScheduledForInsert());
    }

    public function testIsScheduledForInsert()
    {
        $object1 = $this->createTestObject();
        $this->storage->scheduleForExtraInsert($object1);
        $this->assertTrue($this->storage->isScheduledForInsert($object1));

        $object2 = $this->createTestObject();
        $this->assertTrue($this->storage->isScheduledForInsert($object2));

        $object3 = $this->createTestObject();
        $object3->testProperty = 5;
        $this->assertFalse($this->storage->isScheduledForInsert($object3));
    }

    /**
     * @return \stdClass
     */
    protected function createTestObject()
    {
        $object = new \stdClass();
        $object->testProperty = 1;
        $object->testProperty2 = 2;

        return $object;
    }
}
