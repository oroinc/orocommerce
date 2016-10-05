<?php

namespace Oro\Bundle\CommerceEntityBundle\Tests\Unit\Storage;

use Oro\Bundle\CommerceEntityBundle\Storage\ExtraActionEntityStorage;

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
        $object = new \stdClass();

        $this->storage->scheduleForExtraInsert($object);
        $this->assertSame(['stdClass' => [$object]], $this->storage->getScheduledForInsert());
    }

    public function testClearScheduledForInsert()
    {
        $this->storage->scheduleForExtraInsert(new \stdClass());
        $this->storage->clearScheduledForInsert();
        $this->assertEmpty($this->storage->getScheduledForInsert());
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
}
