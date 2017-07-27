<?php

namespace Oro\Bundle\PricingBundle\Tests\Unit\Provider;

use Oro\Bundle\PricingBundle\Provider\PriceListDefaultValueProvider;
use Oro\Bundle\PricingBundle\Provider\PriceListProvider;
use Oro\Bundle\PricingBundle\Sharding\ShardManager;

class PriceListDefaultValueProviderTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var PriceListDefaultValueProvider
     */
    private $priceListDefaultValueProvider;

    /**
     * @var PriceListProvider|\PHPUnit_Framework_MockObject_MockObject
     */
    private $priceListProvider;

    /**
     * @var ShardManager|\PHPUnit_Framework_MockObject_MockObject
     */
    private $shardManager;

    /**
     * {@inheritDoc}
     */
    protected function setUp()
    {
        $this->shardManager = $this->createMock(ShardManager::class);
        $this->priceListProvider = $this->createMock(PriceListProvider::class);
        $this->priceListDefaultValueProvider = new PriceListDefaultValueProvider(
            $this->priceListProvider,
            $this->shardManager
        );
    }

    public function testGetDefaultPriceListIdWhenShardingDisabled()
    {
        $this->shardManager
            ->expects(static::once())
            ->method('isShardingEnabled')
            ->willReturn(false);

        $this->priceListProvider
            ->expects(static::never())
            ->method('getDefaultPriceListId');

        $priceListId = $this->priceListDefaultValueProvider->getDefaultPriceListId();
        static::assertNull($priceListId);
    }

    public function testGetDefaultPriceListIdWhenShardingEnabled()
    {
        $this->shardManager
            ->expects(static::once())
            ->method('isShardingEnabled')
            ->willReturn(true);

        $defaultPriceListId = 1;
        $this->priceListProvider
            ->expects(static::once())
            ->method('getDefaultPriceListId')
            ->willReturn($defaultPriceListId);

        $priceListId = $this->priceListDefaultValueProvider->getDefaultPriceListId();
        static::assertSame($defaultPriceListId, $priceListId);
    }
}
