<?php

namespace Oro\Bundle\InventoryBundle\Tests\Functional\Api\RestJsonApi;

use Oro\Bundle\ApiBundle\Tests\Functional\RestJsonApiUpdateListTestCase;
use Oro\Bundle\InventoryBundle\Entity\InventoryLevel;
use Oro\Bundle\InventoryBundle\Tests\Functional\DataFixtures\UpdateInventoryLevelsQuantities;
use Oro\Bundle\TestFrameworkBundle\Tests\Functional\DataFixtures\LoadBusinessUnit;
use Oro\Bundle\TestFrameworkBundle\Tests\Functional\DataFixtures\LoadOrganization;

/**
 * @group CommunityEdition
 * @dbIsolationPerTest
 */
class InventoryLevelUpdateListTest extends RestJsonApiUpdateListTestCase
{
    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->loadFixtures([
            LoadOrganization::class,
            LoadBusinessUnit::class,
            UpdateInventoryLevelsQuantities::class
        ]);
    }

    public function testTryToCreateInventoryLevels(): void
    {
        $operationId = $this->processUpdateList(
            InventoryLevel::class,
            ['data' => [['type' => 'inventorylevels', 'attributes' => ['quantity' => 22]]]],
            false
        );

        $this->assertAsyncOperationErrors(
            [
                [
                    'id'     => $operationId . '-1-1',
                    'status' => 405,
                    'title'  => 'action not allowed exception',
                    'detail' => 'The action is not allowed.',
                    'source' => ['pointer' => '/data/0']
                ]
            ],
            $operationId
        );
    }

    public function testUpdateWarehouses(): void
    {
        $inventoryLevelId = $this->getReference('inventory_level.product_unit_precision.product-1.liter')->getId();
        $this->processUpdateList(
            InventoryLevel::class,
            [
                'data' => [
                    [
                        'type'       => 'inventorylevels',
                        'id'         => (string)$inventoryLevelId,
                        'meta'       => ['update' => true],
                        'attributes' => ['quantity' => 22]
                    ]
                ]
            ]
        );

        $inventoryLevel = $this->getEntityManager()->find(InventoryLevel::class, $inventoryLevelId);
        self::assertEquals(22, $inventoryLevel->getQuantity());
    }
}
