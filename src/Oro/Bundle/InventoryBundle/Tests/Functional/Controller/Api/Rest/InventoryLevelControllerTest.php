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
class InventoryLevelControllerTest extends WebTestCase
{
    protected function setUp()
    {
        $this->initClient(
            [],
            $this->generateWsseAuthHeader()
        );
        $this->loadFixtures(
            [
                LoadInventoryLevels::class,
            ]
        );
    }

    public function testDeleteAction()
    {
        /** @var InventoryLevel $entity */
        $entity = $this->getInventoryLevelReference(
            'product_unit_precision.product.1.liter'
        );
        $this->client->request(
            Request::METHOD_DELETE,
            $this->getUrl('oro_api_inventory_delete_inventory_level', ['id' => $entity->getId()])
        );
        $result = $this->client->getResponse();
        $this->assertEmptyResponseStatusCodeEquals($result, Response::HTTP_NO_CONTENT);
    }

    /**
     * @param string $precisionReference
     * @return InventoryLevel
     */
    protected function getInventoryLevelReference($precisionReference)
    {
        return $this->getReference(
            sprintf('inventory_level.%s', $precisionReference)
        );
    }
}
