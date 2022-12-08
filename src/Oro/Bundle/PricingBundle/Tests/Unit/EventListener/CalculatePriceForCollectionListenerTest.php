<?php

declare(strict_types=1);

namespace Oro\Bundle\PricingBundle\Tests\Unit\EventListener;

use Oro\Bundle\PricingBundle\EventListener\CalculatePriceForCollectionListener;
use Oro\Bundle\PricingBundle\Model\ProductPriceScopeCriteriaInterface;
use Oro\Bundle\PricingBundle\Model\ProductPriceScopeCriteriaRequestHandler;
use Oro\Bundle\PricingBundle\Provider\QuickAddCollectionPriceProvider;
use Oro\Bundle\ProductBundle\Event\QuickAddRowsCollectionReadyEvent;
use Oro\Bundle\ProductBundle\Model\QuickAddRow;
use Oro\Bundle\ProductBundle\Model\QuickAddRowCollection;

class CalculatePriceForCollectionListenerTest extends \PHPUnit\Framework\TestCase
{
    private QuickAddCollectionPriceProvider|\PHPUnit\Framework\MockObject\MockObject $quickAddCollectionPriceProvider;

    private CalculatePriceForCollectionListener $listener;

    private ProductPriceScopeCriteriaInterface|\PHPUnit\Framework\MockObject\MockObject $productPriceScopeCriteria;

    protected function setUp(): void
    {
        $this->quickAddCollectionPriceProvider = $this->createMock(QuickAddCollectionPriceProvider::class);
        $scopeCriteriaRequestHandler = $this->createMock(ProductPriceScopeCriteriaRequestHandler::class);

        $this->listener = new CalculatePriceForCollectionListener(
            $this->quickAddCollectionPriceProvider,
            $scopeCriteriaRequestHandler
        );

        $this->productPriceScopeCriteria = $this->createMock(ProductPriceScopeCriteriaInterface::class);
        $scopeCriteriaRequestHandler
            ->expects(self::any())
            ->method('getPriceScopeCriteria')
            ->willReturn($this->productPriceScopeCriteria);
    }

    public function testOnQuickAddRowsCollectionReadyWhenIsEmpty(): void
    {
        $collection = new QuickAddRowCollection();
        $quickAddRowsCollectionReadyEvent = new QuickAddRowsCollectionReadyEvent($collection);

        $this->quickAddCollectionPriceProvider
            ->expects(self::never())
            ->method(self::anything());

        $this->listener->onQuickAddRowsCollectionReady($quickAddRowsCollectionReadyEvent);
    }

    public function testOnQuickAddRowsCollectionReady(): void
    {
        $collection = new QuickAddRowCollection([new QuickAddRow(1, 'SKU1', 1, 'item')]);
        $quickAddRowsCollectionReadyEvent = new QuickAddRowsCollectionReadyEvent($collection);

        $this->quickAddCollectionPriceProvider
            ->expects(self::once())
            ->method('addAllPrices')
            ->with($collection, $this->productPriceScopeCriteria);

        $this->listener->onQuickAddRowsCollectionReady($quickAddRowsCollectionReadyEvent);
    }
}
