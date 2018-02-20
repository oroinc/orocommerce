<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\EventListener;

use Oro\Bundle\ConfigBundle\Event\ConfigUpdateEvent;
use Oro\Bundle\ProductBundle\EventListener\Config\DisplaySimpleVariationsListener;
use Oro\Component\Cache\Layout\DataProviderCacheCleaner;

class DisplaySimpleVariationsListenerTest extends \PHPUnit_Framework_TestCase
{
    const CONFIG_KEY = 'oro_product.display_simple_variations';

    /** @var  DataProviderCacheCleaner|\PHPUnit_Framework_MockObject_MockObject */
    protected $cacheClearer;

    /** @var  DataProviderCacheCleaner|\PHPUnit_Framework_MockObject_MockObject */
    protected $categoryCacheClearer;

    /** @var  DisplaySimpleVariationsListener */
    protected $eventListener;

    /**
     * {@inheritdoc}
     */
    public function setUp()
    {
        $this->cacheClearer = $this->createMock(DataProviderCacheCleaner::class);
        $this->categoryCacheClearer = $this->createMock(DataProviderCacheCleaner::class);

        $this->eventListener = new DisplaySimpleVariationsListener(
            $this->cacheClearer,
            $this->categoryCacheClearer,
            self::CONFIG_KEY
        );
    }

    /**
     * {@inheritdoc}
     */
    public function tearDown()
    {
        unset(
            $this->cacheClearer,
            $this->categoryCacheClearer,
            $this->eventListener
        );
    }

    public function testConfigWasChanged()
    {
        $event = $this->createMock(ConfigUpdateEvent::class);
        $event->expects($this->once())
            ->method('isChanged')
            ->with(self::CONFIG_KEY)
            ->willReturn(true);

        $this->cacheClearer->expects($this->once())
            ->method('clearCache');

        $this->categoryCacheClearer->expects($this->once())
            ->method('clearCache');

        $this->eventListener->onUpdateAfter($event);
    }

    public function testConfigWasNotChanged()
    {
        $event = $this->createMock(ConfigUpdateEvent::class);
        $event->expects($this->once())
            ->method('isChanged')
            ->with(self::CONFIG_KEY)
            ->willReturn(false);

        $this->cacheClearer->expects($this->never())
            ->method('clearCache');

        $this->categoryCacheClearer->expects($this->never())
            ->method('clearCache');

        $this->eventListener->onUpdateAfter($event);
    }
}
