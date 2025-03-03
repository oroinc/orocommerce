<?php

namespace Oro\Bundle\ShoppingListBundle\Tests\Unit\Layout\DataProvider;

use Oro\Bundle\PricingBundle\SubtotalProcessor\TotalProcessorProvider;
use Oro\Bundle\ShoppingListBundle\Entity\ShoppingList;
use Oro\Bundle\ShoppingListBundle\Layout\DataProvider\ShoppingListTotalsDataProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ShoppingListTotalsDataProviderTest extends TestCase
{
    private TotalProcessorProvider&MockObject $totalProcessorProvider;
    private ShoppingListTotalsDataProvider $provider;

    #[\Override]
    protected function setUp(): void
    {
        $this->totalProcessorProvider = $this->createMock(TotalProcessorProvider::class);

        $this->provider = new ShoppingListTotalsDataProvider($this->totalProcessorProvider);
    }

    public function testGetTotalWithSubtotalsAsArray(): void
    {
        $shoppingList = $this->createMock(ShoppingList::class);
        $totals = ['sample-totals'];

        $this->totalProcessorProvider->expects(self::once())
            ->method('getTotalWithSubtotalsAsArray')
            ->with($shoppingList)
            ->willReturn($totals);

        self::assertEquals($totals, $this->provider->getTotalWithSubtotalsAsArray($shoppingList));
    }
}
