<?php

namespace Oro\Bundle\B2BEntityBundle\Tests\Unit\Storage;

use Oro\Bundle\B2BEntityBundle\Storage\ExtraActionEntityStorage;
use Oro\Bundle\B2BEntityBundle\Tests\Stub\ObjectIdentifierAware;

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
        $this->assertSame([$object->getObjectIdentifier() => $object], $this->storage->getScheduledForInsert());
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
     * @dataProvider invalidTypeDataProvider
     * @param mixed $type
     * @param string $message
     */
    public function testInvalidType($type, $message)
    {
        $this->setExpectedException('\InvalidArgumentException', $message);

        $this->storage->scheduleForExtraInsert($type);
    }

    /**
     * @return array
     */
    public function invalidTypeDataProvider()
    {
        return [
            [[], 'Expected type is object, array given'],
            ['string', 'Expected type is object, string given'],
            [null, 'Expected type is object, NULL given'],
            [1, 'Expected type is object, integer given'],
        ];
    }

    /**
     * @return ObjectIdentifierAware
     */
    protected function createTestObject()
    {
        $object = new ObjectIdentifierAware(1, 2);

        return $object;
    }
}
