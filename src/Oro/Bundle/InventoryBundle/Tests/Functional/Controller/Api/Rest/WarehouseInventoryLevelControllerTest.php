<?php

namespace Oro\Bundle\InventoryBundle\Tests\Functional\Controller\Api\Rest;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\InventoryBundle\Entity\InventoryLevel;
use Oro\Bundle\InventoryBundle\Tests\Functional\DataFixtures\LoadInventoryLevels;

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
                'Oro\Bundle\InventoryBundle\Tests\Functional\DataFixtures\LoadInventoryLevels',
            ]
        );
    }

    public function testDeleteAction()
    {
        /** @var InventoryLevel $entity */
        $entity = $this->getWarehouseInventoryLevelReference(
            LoadInventoryLevels::WAREHOUSE1,
            'product_unit_precision.product.1.liter'
        );
        $this->client->request(
            Request::METHOD_DELETE,
            $this->getUrl('oro_api_warehouse_delete_warehouse_inventory_level', ['id' => $entity->getId()])
        );
        $result = $this->client->getResponse();
        $this->assertEmptyResponseStatusCodeEquals($result, Response::HTTP_NO_CONTENT);
    }

    /**
     * @param string $warehouseReference
     * @param string $precisionReference
     * @return InventoryLevel
     */
    protected function getWarehouseInventoryLevelReference($warehouseReference, $precisionReference)
    {
        return $this->getReference(
            sprintf('warehouse_inventory_level.%s.%s', $warehouseReference, $precisionReference)
        );
    }
}
