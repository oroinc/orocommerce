<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\EventListener;

use Oro\Bundle\ConfigBundle\Event\ConfigUpdateEvent;
use Oro\Bundle\ProductBundle\EventListener\Config\DisplaySimpleVariationsListener;
use Symfony\Component\Cache\Adapter\AbstractAdapter;

class DisplaySimpleVariationsListenerTest extends \PHPUnit\Framework\TestCase
{
    private const CONFIG_KEY = 'oro_product.display_simple_variations';

    /** @var AbstractAdapter|\PHPUnit\Framework\MockObject\MockObject */
    private $productCache;

    /** @var AbstractAdapter|\PHPUnit\Framework\MockObject\MockObject */
    private $categoryCache;

    /** @var DisplaySimpleVariationsListener */
    private $eventListener;

    #[\Override]
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
        $event = new ConfigUpdateEvent([self::CONFIG_KEY => ['old' => 1, 'new' => 2]], 'global', 0);

        $this->productCache->expects($this->once())
            ->method('clear');

        $this->categoryCache->expects($this->once())
            ->method('clear');

        $this->eventListener->onUpdateAfter($event);
    }

    public function testConfigWasNotChanged()
    {
        $event = new ConfigUpdateEvent([], 'global', 0);

        $this->productCache->expects($this->never())
            ->method('clear');

        $this->categoryCache->expects($this->never())
            ->method('clear');

        $this->eventListener->onUpdateAfter($event);
    }
}
