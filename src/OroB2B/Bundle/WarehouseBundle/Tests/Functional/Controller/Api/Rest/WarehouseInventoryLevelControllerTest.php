<?php

namespace OroB2B\Bundle\WarehouseBundle\Tests\Functional\Controller\Api\Rest;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

use OroB2B\Bundle\WarehouseBundle\Entity\WarehouseInventoryLevel;
use OroB2B\Bundle\WarehouseBundle\Tests\Functional\DataFixtures\LoadWarehousesAndInventoryLevels;

/**
 * @dbIsolation
 */
class WarehouseInventoryLevelControllerTest extends WebTestCase
{
    protected function setUp()
    {
        $this->initClient(
            [],
            $this->generateWsseAuthHeader()
        );
        $this->loadFixtures(
            [
                'OroB2B\Bundle\WarehouseBundle\Tests\Functional\DataFixtures\LoadWarehousesAndInventoryLevels',
            ]
        );
    }

    public function testDeleteAction()
    {
        /** @var WarehouseInventoryLevel $entity */
        $entity = $this->getWarehouseInventoryLevelReference(
            LoadWarehousesAndInventoryLevels::WAREHOUSE1,
            'product_unit_precision.product.1.liter'
        );
        $this->client->request(
            Request::METHOD_DELETE,
            $this->getUrl('orob2b_api_warehouse_delete_warehouse_inventory_level', ['id' => $entity->getId()])
        );
        $result = $this->client->getResponse();
        $this->assertEmptyResponseStatusCodeEquals($result, Response::HTTP_NO_CONTENT);
    }

    /**
     * @param string $warehouseReference
     * @param string $precisionReference
     * @return WarehouseInventoryLevel
     */
    protected function getWarehouseInventoryLevelReference($warehouseReference, $precisionReference)
    {
        return $this->getReference(
            sprintf('warehouse_inventory_level.%s.%s', $warehouseReference, $precisionReference)
        );
    }
}
