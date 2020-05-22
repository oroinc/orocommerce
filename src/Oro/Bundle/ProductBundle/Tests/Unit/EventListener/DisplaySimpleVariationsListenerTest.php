<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\EventListener;

use Doctrine\Common\Cache\CacheProvider;
use Oro\Bundle\ConfigBundle\Event\ConfigUpdateEvent;
use Oro\Bundle\ProductBundle\EventListener\Config\DisplaySimpleVariationsListener;

class DisplaySimpleVariationsListenerTest extends \PHPUnit\Framework\TestCase
{
    const CONFIG_KEY = 'oro_product.display_simple_variations';

    /** @var CacheProvider|\PHPUnit\Framework\MockObject\MockObject */
    protected $productCache;

    /** @var CacheProvider|\PHPUnit\Framework\MockObject\MockObject */
    protected $categoryCache;

    /** @var  DisplaySimpleVariationsListener */
    protected $eventListener;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        $this->productCache = $this->createMock(CacheProvider::class);
        $this->categoryCache = $this->createMock(CacheProvider::class);

        $this->eventListener = new DisplaySimpleVariationsListener(
            $this->productCache,
            $this->categoryCache,
            self::CONFIG_KEY
        );
    }

    public function testConfigWasChanged()
    {
        $event = $this->createMock(ConfigUpdateEvent::class);
        $event->expects($this->once())
            ->method('isChanged')
            ->with(self::CONFIG_KEY)
            ->willReturn(true);

        $this->productCache->expects($this->once())
            ->method('deleteAll');

        $this->categoryCache->expects($this->once())
            ->method('deleteAll');

        $this->eventListener->onUpdateAfter($event);
    }

    public function testConfigWasNotChanged()
    {
        $event = $this->createMock(ConfigUpdateEvent::class);
        $event->expects($this->once())
            ->method('isChanged')
            ->with(self::CONFIG_KEY)
            ->willReturn(false);

        $this->productCache->expects($this->never())
            ->method('deleteAll');

        $this->categoryCache->expects($this->never())
            ->method('deleteAll');

        $this->eventListener->onUpdateAfter($event);
    }
}
