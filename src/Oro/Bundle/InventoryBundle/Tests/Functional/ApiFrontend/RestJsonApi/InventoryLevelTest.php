<?php

declare(strict_types=1);

namespace Oro\Bundle\InventoryBundle\Tests\Functional\ApiFrontend\RestJsonApi;

use Oro\Bundle\CustomerBundle\Tests\Functional\ApiFrontend\DataFixtures\LoadAdminCustomerUserData;
use Oro\Bundle\FrontendBundle\Tests\Functional\ApiFrontend\FrontendRestJsonApiTestCase;
use Oro\Bundle\InventoryBundle\Entity\InventoryLevel;
use Oro\Bundle\InventoryBundle\Tests\Functional\DataFixtures\UpdateInventoryLevelsQuantities;
use Oro\Bundle\ProductBundle\Entity\Product;

/**
 * @group CommunityEdition
 */
class InventoryLevelTest extends FrontendRestJsonApiTestCase
{
    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();
        $this->loadFixtures([
            LoadAdminCustomerUserData::class,
            UpdateInventoryLevelsQuantities::class
        ]);
    }

    #[\Override]
    protected function postFixtureLoad(): void
    {
        parent::postFixtureLoad();

        /** @var Product $product2 */
        $product2 = $this->getReference('product-2');
        $product2->setStatus(Product::STATUS_DISABLED);
        $this->getEntityManager()->flush();
    }

    private function getInventoryLevel(): InventoryLevel
    {
        /** @var InventoryLevel $inventoryLevel */
        $inventoryLevel = $this->getReference('inventory_level.product_unit_precision.product-1.liter');

        return $inventoryLevel;
    }

    public function testGetListFilteredByProduct(): void
    {
        $response = $this->cget(
            ['entity' => 'inventorylevels'],
            ['filter' => ['product.sku' => ['@product-1->sku', '@product-2->sku']]]
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
