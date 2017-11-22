<?php

namespace Oro\Bundle\CheckoutBundle\Tests\Functional\Operation;

use Oro\Bundle\InventoryBundle\Tests\Functional\DataFixtures\UpdateInventoryLevelsQuantities;
use Oro\Bundle\OrderBundle\Tests\Functional\DataFixtures\LoadOrderLineItemData;

/**
 * @group CommunityEdition
 */
class OrderFrontendOperationsTest extends OrderFrontendOperationsTestCase
{
    /**
     * {@inheritDoc}
     */
    protected function getFixtures()
    {
        return [
            LoadOrderLineItemData::class,
            UpdateInventoryLevelsQuantities::class
        ];
    }
}
