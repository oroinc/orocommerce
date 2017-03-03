<?php

namespace Oro\Bundle\InventoryBundle\Tests\Functional\Action;

use Oro\Bundle\InventoryBundle\Entity\InventoryLevel;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

class InventoryLevelAcitonTest extends WebTestCase
{
    protected function setUp()
    {
        $this->initClient([], $this->generateBasicAuthHeader());
        $this->client->useHashNavigation(true);
        $this->loadFixtures(['@OroInventoryBundle/Tests/Functional/DataFixtures/inventory_level.yml']);
    }

    public function testDelete()
    {
        /** @var InventoryLevel $inventoryLevel */
        $inventoryLevel = $this->getReference(
            sprintf(
                'inventory_level.%s',
                'product_unit_precision.product-1.liter'
            )
        );
        $levelId = $inventoryLevel->getId();

        $this->client->request(
            'GET',
            $this->getUrl(
                'oro_action_operation_execute',
                [
                    'operationName' => 'oro_inventory_level_order_delete',
                    'entityId' => $levelId,
                    'entityClass' => InventoryLevel::class,
                ]
            ),
            [],
            [],
            ['HTTP_X_REQUESTED_WITH' => 'XMLHttpRequest']
        );
        static::assertJsonResponseStatusCodeEquals($this->client->getResponse(), 200);

        static::getContainer()->get('doctrine')->getManagerForClass(InventoryLevel::class)->clear();

        $removedLevel = static::getContainer()
            ->get('doctrine')
            ->getRepository('OroInventoryBundle:InventoryLevel')
            ->find($levelId);

        static::assertNull($removedLevel);
    }
}
