<?php

namespace Oro\Bundle\PricingBundle\Tests\Unit\Entity\EntityListener;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\PricingBundle\Entity\EntityListener\RemoveFromConfigurationPriceListEntityListener;
use Oro\Bundle\PricingBundle\Entity\PriceList;
use Oro\Bundle\PricingBundle\SystemConfig\PriceListConfig;
use Oro\Bundle\PricingBundle\SystemConfig\PriceListConfigConverter;
use Oro\Component\Testing\Unit\EntityTrait;
use PHPUnit\Framework\TestCase;

class RemoveFromConfigurationPriceListEntityListenerTest extends TestCase
{
    use EntityTrait;

    /**
     * @var ConfigManager|\PHPUnit\Framework\MockObject\MockObject
     */
    private $configManager;

    /**
     * @var PriceListConfigConverter|\PHPUnit\Framework\MockObject\MockObject
     */
    private $configConverter;

    /**
     * @var RemoveFromConfigurationPriceListEntityListener
     */
    private $listener;

    protected function setUp(): void
    {
        $this->configManager = $this->createMock(ConfigManager::class);
        $this->configConverter = $this->createMock(PriceListConfigConverter::class);

        $this->listener = new RemoveFromConfigurationPriceListEntityListener(
            $this->configManager,
            $this->configConverter
        );
    }

    public function testPreRemove()
    {
        $config = [];
        $configLists = [
            new PriceListConfig($this->createPriceList(1)),
            new PriceListConfig($this->createPriceList(2)),
            new PriceListConfig($this->createPriceList(3)),
        ];

        $this->configManager
            ->expects(static::once())
            ->method('get')
            ->with('oro_pricing.default_price_lists')
            ->willReturn($config);

        $this->configConverter
            ->expects(static::once())
            ->method('convertFromSaved')
            ->with($config)
            ->willReturn($configLists);

        $this->configManager
            ->expects(static::once())
            ->method('set')
            ->with(
                'oro_pricing.default_price_lists',
                [
                    new PriceListConfig($this->createPriceList(1)),
                    new PriceListConfig($this->createPriceList(3)),
                ]
            );
        $this->configManager
            ->expects(static::once())
            ->method('flush');

        $this->listener->preRemove($this->createPriceList(2));
    }

    public function testPreRemoveWithoutChanges()
    {
        $config = [];
        $configLists = [
            new PriceListConfig($this->createPriceList(1)),
            new PriceListConfig($this->createPriceList(2)),
            new PriceListConfig($this->createPriceList(3)),
        ];

        $this->configManager
            ->expects(static::once())
            ->method('get')
            ->with('oro_pricing.default_price_lists')
            ->willReturn($config);

        $this->configConverter
            ->expects(static::once())
            ->method('convertFromSaved')
            ->with($config)
            ->willReturn($configLists);

        $this->configManager
            ->expects(static::never())
            ->method('set');
        $this->configManager
            ->expects(static::never())
            ->method('flush');

        $this->listener->preRemove($this->createPriceList(4));
    }

    private function createPriceList(int $id): PriceList
    {
        return $this->getEntity(PriceList::class, ['id' => $id]);
    }
}
