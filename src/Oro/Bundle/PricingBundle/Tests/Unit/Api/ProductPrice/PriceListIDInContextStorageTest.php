<?php

namespace Oro\Bundle\PricingBundle\Tests\Unit\Api\ProductPrice;

use Oro\Bundle\PricingBundle\Api\ProductPrice\PriceListIDInContextStorage;
use Oro\Component\ChainProcessor\ContextInterface;
use PHPUnit\Framework\TestCase;

class PriceListIDInContextStorageTest extends TestCase
{
    const ID = 57;

    /**
     * @var ContextInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private $context;

    /**
     * @var PriceListIDInContextStorage
     */
    private $storage;

    protected function setUp()
    {
        $this->context = $this->createMock(ContextInterface::class);

        $this->storage = new PriceListIDInContextStorage();
    }

    public function testStoreAndGet()
    {
        $this->context
            ->expects(static::once())
            ->method('set')
            ->with('price_list_id')
            ->willReturn(self::ID);

        $this->context
            ->expects(static::once())
            ->method('get')
            ->with('price_list_id')
            ->willReturn(self::ID);

        $this->context
            ->expects(static::once())
            ->method('has')
            ->with('price_list_id')
            ->willReturn(true);

        $this->storage->store(self::ID, $this->context);
        static::assertSame(self::ID, $this->storage->get($this->context));
    }

    public function testGetException()
    {
        $this->expectException(\Exception::class);

        $this->context
            ->expects(static::once())
            ->method('has')
            ->with('price_list_id')
            ->willReturn(false);

        $this->storage->get($this->context);
    }
}
