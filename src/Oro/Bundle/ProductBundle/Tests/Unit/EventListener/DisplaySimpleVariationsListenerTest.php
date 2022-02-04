<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\EventListener;

use Oro\Bundle\ConfigBundle\Event\ConfigUpdateEvent;
use Oro\Bundle\ProductBundle\EventListener\Config\DisplaySimpleVariationsListener;
use Symfony\Component\Cache\Adapter\AbstractAdapter;

class DisplaySimpleVariationsListenerTest extends \PHPUnit\Framework\TestCase
{
    const CONFIG_KEY = 'oro_product.display_simple_variations';

    /** @var AbstractAdapter|\PHPUnit\Framework\MockObject\MockObject */
    protected $productCache;

    /** @var AbstractAdapter|\PHPUnit\Framework\MockObject\MockObject */
    protected $categoryCache;

    /** @var DisplaySimpleVariationsListener */
    protected $eventListener;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        $this->productCache = $this->createMock(AbstractAdapter::class);
        $this->categoryCache = $this->createMock(AbstractAdapter::class);

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
            ->method('clear');

        $this->categoryCache->expects($this->once())
            ->method('clear');

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
            ->method('clear');

        $this->categoryCache->expects($this->never())
            ->method('clear');

        $this->eventListener->onUpdateAfter($event);
    }
}
