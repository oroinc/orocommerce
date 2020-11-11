<?php

namespace Oro\Bundle\ShoppingListBundle\Tests\Unit\Layout\DataProvider;

use Oro\Bundle\PricingBundle\SubtotalProcessor\TotalProcessorProvider;
use Oro\Bundle\ShoppingListBundle\Entity\ShoppingList;
use Oro\Bundle\ShoppingListBundle\Layout\DataProvider\ShoppingListTotalsDataProvider;

class ShoppingListTotalsDataProviderTest extends \PHPUnit\Framework\TestCase
{
    /** @var TotalProcessorProvider */
    private $totalProcessorProvider;

    /** @var ShoppingListTotalsDataProvider */
    private $provider;

    protected function setUp(): void
    {
        $this->totalProcessorProvider = $this->createMock(TotalProcessorProvider::class);

        $this->provider = new ShoppingListTotalsDataProvider($this->totalProcessorProvider);
    }

    public function testGetTotalWithSubtotalsAsArray(): void
    {
        $shoppingList = $this->createMock(ShoppingList::class);
        $totals = ['sample-totals'];

        $this->totalProcessorProvider
            ->expects($this->once())
            ->method('getTotalWithSubtotalsAsArray')
            ->with($shoppingList)
            ->willReturn($totals);

        $this->assertEquals($totals, $this->provider->getTotalWithSubtotalsAsArray($shoppingList));
    }
}
