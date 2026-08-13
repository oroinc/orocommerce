<?php

declare(strict_types=1);

namespace Oro\Bundle\OrderBundle\Tests\Unit\EventListener;

use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\OrderBundle\Entity\OrderLineItem;
use Oro\Bundle\OrderBundle\EventListener\PreloadOrderLineItemTaxValuesListener;
use Oro\Bundle\TaxBundle\Event\LoadTaxBeforeEvent;
use Oro\Bundle\TaxBundle\Manager\TaxManager;
use Oro\Component\Testing\ReflectionUtil;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class PreloadOrderLineItemTaxValuesListenerTest extends TestCase
{
    private TaxManager&MockObject $taxManager;

    private PreloadOrderLineItemTaxValuesListener $listener;

    #[\Override]
    protected function setUp(): void
    {
        $this->taxManager = $this->createMock(TaxManager::class);

        $this->listener = new PreloadOrderLineItemTaxValuesListener($this->taxManager);
    }

    public function testOnLoadTaxBeforePreloadsTaxValuesForOrderLineItems(): void
    {
        $firstLineItem = new OrderLineItem();
        ReflectionUtil::setId($firstLineItem, 10);
        $secondLineItem = new OrderLineItem();
        ReflectionUtil::setId($secondLineItem, 20);

        $order = new Order();
        $order->addLineItem($firstLineItem);
        $order->addLineItem($secondLineItem);

        $this->taxManager
            ->expects(self::once())
            ->method('preloadTaxValues')
            ->with(OrderLineItem::class, [10, 20]);

        $this->listener->onLoadTaxBefore(new LoadTaxBeforeEvent($order));
    }

    public function testOnLoadTaxBeforeSkipsLineItemsWithoutIdentifier(): void
    {
        $persistedLineItem = new OrderLineItem();
        ReflectionUtil::setId($persistedLineItem, 10);

        $order = new Order();
        $order->addLineItem($persistedLineItem);
        $order->addLineItem(new OrderLineItem());

        $this->taxManager
            ->expects(self::once())
            ->method('preloadTaxValues')
            ->with(OrderLineItem::class, [10]);

        $this->listener->onLoadTaxBefore(new LoadTaxBeforeEvent($order));
    }

    public function testOnLoadTaxBeforeDoesNothingWhenOrderHasNoLineItems(): void
    {
        $this->taxManager
            ->expects(self::never())
            ->method('preloadTaxValues');

        $this->listener->onLoadTaxBefore(new LoadTaxBeforeEvent(new Order()));
    }

    public function testOnLoadTaxBeforeDoesNothingWhenObjectIsNotOrder(): void
    {
        $this->taxManager
            ->expects(self::never())
            ->method('preloadTaxValues');

        $this->listener->onLoadTaxBefore(new LoadTaxBeforeEvent(new OrderLineItem()));
    }
}
