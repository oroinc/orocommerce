<?php

namespace Oro\Bundle\ShoppingListBundle\Layout\DataProvider;

use Oro\Bundle\PricingBundle\SubtotalProcessor\TotalProcessorProvider;
use Oro\Bundle\ShoppingListBundle\Entity\ShoppingList;

/**
 * Layouts data provider for shopping list totals
 */
class ShoppingListTotalsDataProvider
{
    /** @var TotalProcessorProvider */
    private $totalProcessorProvider;

    public function __construct(TotalProcessorProvider $totalProcessorProvider)
    {
        $this->totalProcessorProvider = $totalProcessorProvider;
    }

    public function getTotalWithSubtotalsAsArray(ShoppingList $shoppingList): array
    {
        return $this->totalProcessorProvider->getTotalWithSubtotalsAsArray($shoppingList);
    }
}
