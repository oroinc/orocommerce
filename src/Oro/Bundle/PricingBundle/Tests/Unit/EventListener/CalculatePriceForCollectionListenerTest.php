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
    /** @var QuickAddCollectionPriceProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $quickAddCollectionPriceProvider;

    /** @var ProductPriceScopeCriteriaRequestHandler|\PHPUnit\Framework\MockObject\MockObject */
    private $scopeCriteriaRequestHandler;

    /** @var CalculatePriceForCollectionListener */
    private $listener;

    protected function setUp(): void
    {
        $this->quickAddCollectionPriceProvider = $this->createMock(QuickAddCollectionPriceProvider::class);
        $this->scopeCriteriaRequestHandler = $this->createMock(ProductPriceScopeCriteriaRequestHandler::class);

        $this->listener = new CalculatePriceForCollectionListener(
            $this->quickAddCollectionPriceProvider,
            $this->scopeCriteriaRequestHandler
        );
    }

    public function testOnQuickAddRowsCollectionReadyWhenIsEmpty(): void
    {
        $collection = new QuickAddRowCollection();

        $this->scopeCriteriaRequestHandler->expects(self::never())
            ->method('getPriceScopeCriteria');
        $this->quickAddCollectionPriceProvider->expects(self::never())
            ->method('addAllPrices');

        $event = new QuickAddRowsCollectionReadyEvent($collection);
        $this->listener->onQuickAddRowsCollectionReady($event);
    }

    public function testOnQuickAddRowsCollectionReady(): void
    {
        $collection = new QuickAddRowCollection([new QuickAddRow(1, 'SKU1', 1, 'item')]);
        $productPriceScopeCriteria = $this->createMock(ProductPriceScopeCriteriaInterface::class);

        $this->scopeCriteriaRequestHandler->expects(self::any())
            ->method('getPriceScopeCriteria')
            ->willReturn($productPriceScopeCriteria);

        $this->quickAddCollectionPriceProvider->expects(self::once())
            ->method('addAllPrices')
            ->with(self::identicalTo($collection), self::identicalTo($productPriceScopeCriteria));

        $event = new QuickAddRowsCollectionReadyEvent($collection);
        $this->listener->onQuickAddRowsCollectionReady($event);
    }
}
