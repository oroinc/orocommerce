<?php

declare(strict_types=1);

namespace Oro\Bundle\InventoryBundle\Tests\Functional\ApiFrontend\RestJsonApi;

use Oro\Bundle\CustomerBundle\Tests\Functional\ApiFrontend\DataFixtures\LoadBuyerCustomerUserData;
use Oro\Bundle\FrontendBundle\Tests\Functional\ApiFrontend\FrontendRestJsonApiTestCase;
use Oro\Bundle\InventoryBundle\Entity\InventoryLevel;
use Oro\Bundle\InventoryBundle\Tests\Functional\DataFixtures\UpdateInventoryLevelsQuantities;

/**
 * @group CommunityEdition
 */
class InventoryLevelForBuyerTest extends FrontendRestJsonApiTestCase
{
    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();
        $this->loadFixtures([
            LoadBuyerCustomerUserData::class,
            UpdateInventoryLevelsQuantities::class
        ]);
    }

    private function getInventoryLevel(): InventoryLevel
    {
        /** @var InventoryLevel $inventoryLevel */
        $inventoryLevel = $this->getReference('inventory_level.product_unit_precision.product-1.liter');

        return $inventoryLevel;
    }

    public function testGetList(): void
    {
        $response = $this->cget(
            ['entity' => 'inventorylevels'],
            ['filter[product.sku]' => '@product-1->sku']
        );

        $this->assertResponseContains('cget_inventory_level_filter_product.yml', $response);
    }

    public function testGet(): void
    {
        $inventoryLevelId = $this->getInventoryLevel()->getId();
        $response = $this->get(
            ['entity' => 'inventorylevels', 'id' => (string)$inventoryLevelId]
        );

        $this->assertResponseContains('get_inventory_level.yml', $response);
    }

    public function testTryToUpdate(): void
    {
        $inventoryLevelId = $this->getInventoryLevel()->getId();
        $response = $this->patch(
            ['entity' => 'inventorylevels', 'id' => (string)$inventoryLevelId],
            [
                'data' => [
                    'type' => 'inventorylevels',
                    'id' => (string)$inventoryLevelId,
                    'attributes' => [
                        'quantity' => 17
                    ]
                ]
            ],
            [],
            false
        );

        self::assertMethodNotAllowedResponse($response, 'OPTIONS, GET');
    }

    public function testTryToCreate(): void
    {
        $response = $this->post(
            ['entity' => 'inventorylevels'],
            [
                'data' => [
                    'type' => 'inventorylevels',
                    'attributes' => [
                        'quantity' => 17
                    ]
                ]
            ],
            [],
            false
        );

        self::assertMethodNotAllowedResponse($response, 'OPTIONS, GET');
    }

    public function testTryToDelete(): void
    {
        $inventoryLevelId = $this->getInventoryLevel()->getId();
        $response = $this->delete(
            ['entity' => 'inventorylevels', 'id' => (string)$inventoryLevelId],
            [],
            [],
            false
        );

        self::assertMethodNotAllowedResponse($response, 'OPTIONS, GET');
    }

    public function testTryToDeleteList(): void
    {
        $inventoryLevelId = $this->getInventoryLevel()->getId();
        $response = $this->cdelete(
            ['entity' => 'inventorylevels'],
            ['filter' => ['id' => (string)$inventoryLevelId]],
            [],
            false
        );

        self::assertMethodNotAllowedResponse($response, 'OPTIONS, GET');
    }
}
