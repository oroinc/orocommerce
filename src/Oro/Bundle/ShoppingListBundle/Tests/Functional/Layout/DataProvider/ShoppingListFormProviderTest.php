<?php

namespace Oro\Bundle\ShoppingListBundle\Tests\Functional\Layout\DataProvider;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\ShoppingListBundle\Entity\ShoppingList;
use Oro\Bundle\ShoppingListBundle\Form\Type\ShoppingListType;
use Oro\Bundle\ShoppingListBundle\Layout\DataProvider\ShoppingListFormProvider;

class ShoppingListFormProviderTest extends WebTestCase
{
    /** @var ShoppingListFormProvider */
    protected $dataProvider;

    protected function setUp()
    {
        $this->initClient();

        $this->dataProvider = $this->getContainer()
            ->get('oro_shopping_list.layout.data_provider.shopping_list_form');
    }

    public function testGetShoppingListForm()
    {
        $shoppingList = new ShoppingList();

        $actual = $this->dataProvider->getShoppingListForm($shoppingList);

        $this->assertInstanceOf('\Oro\Bundle\LayoutBundle\Layout\Form\FormAccessorInterface', $actual);
        $this->assertEquals(ShoppingListType::NAME, $actual->getForm()->getName());
    }
}
