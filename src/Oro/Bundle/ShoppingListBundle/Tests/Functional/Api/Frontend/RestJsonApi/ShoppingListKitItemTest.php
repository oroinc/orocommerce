<?php

namespace Oro\Bundle\ShoppingListBundle\Tests\Functional\Api\Frontend\RestJsonApi;

use Oro\Bundle\CustomerBundle\Tests\Functional\Api\Frontend\DataFixtures\LoadAdminCustomerUserData;
use Oro\Bundle\FrontendBundle\Tests\Functional\Api\FrontendRestJsonApiTestCase;

/**
 * @dbIsolationPerTest
 */
class ShoppingListKitItemTest extends FrontendRestJsonApiTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->loadFixtures([
            LoadAdminCustomerUserData::class,
            '@OroShoppingListBundle/Tests/Functional/Api/Frontend/DataFixtures/shopping_list.yml',
        ]);
    }

    public function testGetList(): void
    {
        $response = $this->cget(['entity' => 'shoppinglistkititems']);

        $this->assertResponseContains('cget_kit_line_item.yml', $response);
    }

    public function testGetListFilteredByShoppingListItem(): void
    {
        $response = $this->cget(
            ['entity' => 'shoppinglistkititems'],
            ['filter' => ['lineItem' => '<toString(@kit_line_item1->id)>']]
        );

        $this->assertResponseContains('cget_kit_line_item_filter.yml', $response);
    }

    public function testGet(): void
    {
        $response = $this->get(
            ['entity' => 'shoppinglistkititems', 'id' => '<toString(@product_kit_item1_line_item1->id)>']
        );

        $this->assertResponseContains('get_kit_item_line_item.yml', $response);
    }
}
