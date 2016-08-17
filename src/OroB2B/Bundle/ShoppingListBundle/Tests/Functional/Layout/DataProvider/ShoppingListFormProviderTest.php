<?php

namespace OroB2B\Bundle\ShoppingListBundle\Tests\Functional\Layout\DataProvider;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

use OroB2B\Bundle\ShoppingListBundle\Entity\ShoppingList;
use OroB2B\Bundle\ShoppingListBundle\Form\Type\ShoppingListType;
use OroB2B\Bundle\ShoppingListBundle\Layout\DataProvider\ShoppingListFormProvider;

class ShoppingListFormProviderTest extends WebTestCase
{
    /** @var ShoppingListFormProvider */
    protected $dataProvider;

    protected function setUp()
    {
        $this->initClient();

        $this->dataProvider = $this->getContainer()
            ->get('orob2b_shopping_list.layout.data_provider.shopping_list_form');
    }

    public function testGetShoppingListForm()
    {
        $shoppingList = new ShoppingList();

        $actual = $this->dataProvider->getShoppingListForm($shoppingList);

        $this->assertInstanceOf('\Oro\Bundle\LayoutBundle\Layout\Form\FormAccessorInterface', $actual);
        $this->assertEquals(ShoppingListType::NAME, $actual->getForm()->getName());
    }
}
