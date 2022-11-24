<?php

namespace Oro\Bundle\CommerceEntityBundle\Tests\Unit\Storage;

use Oro\Bundle\CommerceEntityBundle\Storage\ExtraActionEntityStorage;

class ExtraActionEntityStorageTest extends \PHPUnit\Framework\TestCase
{
    /** @var ExtraActionEntityStorage */
    private $storage;

    protected function setUp(): void
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
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage($message);

        $this->storage->scheduleForExtraInsert($type);
    }

    public function invalidTypeDataProvider(): array
    {
        return [
            [[], 'Expected type is object, array given'],
            ['string', 'Expected type is object, string given'],
            [null, 'Expected type is object, NULL given'],
            [1, 'Expected type is object, integer given'],
        ];
    }
}
