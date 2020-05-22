<?php

namespace Oro\Bundle\ShoppingListBundle\Tests\Functional\Controller\Frontend;

use Oro\Bundle\FrontendTestFrameworkBundle\Migrations\Data\ORM\LoadCustomerUserData;
use Oro\Bundle\ShoppingListBundle\Datagrid\Provider\MassAction\AddLineItemMassActionProvider;
use Oro\Bundle\ShoppingListBundle\Entity\ShoppingList;
use Oro\Bundle\ShoppingListBundle\Tests\Functional\DataFixtures\LoadShoppingLists;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

class AjaxMassActionControllerTest extends WebTestCase
{
    protected function setUp(): void
    {
        $this->initClient(
            [],
            $this->generateBasicAuthHeader(LoadCustomerUserData::AUTH_USER, LoadCustomerUserData::AUTH_PW)
        );

        $this->loadFixtures(
            [
                LoadShoppingLists::class,
            ]
        );
    }

    public function testGetMassActionsAction()
    {
        $shoppingList1 = $this->getReference(LoadShoppingLists::SHOPPING_LIST_1);
        $shoppingList2 = $this->getReference(LoadShoppingLists::SHOPPING_LIST_2);
        $shoppingList3 = $this->getReference(LoadShoppingLists::SHOPPING_LIST_3);
        $shoppingList4 = $this->getReference(LoadShoppingLists::SHOPPING_LIST_4);
        $shoppingList5 = $this->getReference(LoadShoppingLists::SHOPPING_LIST_5);
        $shoppingList8 = $this->getReference(LoadShoppingLists::SHOPPING_LIST_8);

        $this->client->request(
            'GET',
            $this->getUrl('oro_shopping_list_frontend_get_mass_actions')
        );
        $result = $this->client->getResponse();
        $this->assertJsonResponseStatusCodeEquals($result, 200);
        $data = json_decode($result->getContent(), true);

        $this->assertEquals(7, count($data));
        $this->assertArrayHasKey($this->getMassActionTitle($shoppingList1), $data);
        $this->assertEquals('addproducts', $data[$this->getMassActionTitle($shoppingList1)]['type']);
        $this->assertArrayHasKey($this->getMassActionTitle($shoppingList2), $data);
        $this->assertEquals('addproducts', $data[$this->getMassActionTitle($shoppingList2)]['type']);
        $this->assertArrayHasKey($this->getMassActionTitle($shoppingList3), $data);
        $this->assertEquals('addproducts', $data[$this->getMassActionTitle($shoppingList3)]['type']);
        $this->assertArrayHasKey($this->getMassActionTitle($shoppingList4), $data);
        $this->assertEquals('addproducts', $data[$this->getMassActionTitle($shoppingList4)]['type']);
        $this->assertArrayHasKey($this->getMassActionTitle($shoppingList5), $data);
        $this->assertEquals('addproducts', $data[$this->getMassActionTitle($shoppingList5)]['type']);
        $this->assertArrayHasKey($this->getMassActionTitle($shoppingList8), $data);
        $this->assertEquals('addproducts', $data[$this->getMassActionTitle($shoppingList8)]['type']);
        $this->assertArrayHasKey(sprintf('%snew', AddLineItemMassActionProvider::NAME_PREFIX), $data);
        $this->assertEquals('window', $data[sprintf('%snew', AddLineItemMassActionProvider::NAME_PREFIX)]['type']);
    }

    /**
     * @param ShoppingList $shoppingList
     * @return string
     */
    private function getMassActionTitle(ShoppingList $shoppingList)
    {
        return sprintf('%slist%d', AddLineItemMassActionProvider::NAME_PREFIX, $shoppingList->getId());
    }
}
