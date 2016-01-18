<?php

namespace OroB2B\Bundle\ShoppingListBundle\Tests\Functional\Provider;

use Oro\Component\Layout\LayoutContext;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

use OroB2B\Bundle\ShoppingListBundle\Entity\ShoppingList;
use OroB2B\Bundle\ShoppingListBundle\Form\Type\ShoppingListType;
use OroB2B\Bundle\ShoppingListBundle\Provider\ShoppingListForm;

class ShoppingListFormTest extends WebTestCase
{
    /** @var LayoutContext */
    protected $context;

    /** @var ShoppingListForm */
    protected $dataProvider;

    protected function setUp()
    {
        $this->initClient();

        $this->context = new LayoutContext();
        $this->dataProvider = $this->getContainer()->get('orob2b_shopping_list.provider.shopping_list_form');
    }

    public function testGetIdentifier()
    {
        $this->assertEquals('orob2b_shopping_list_shopping_list_form', $this->dataProvider->getIdentifier());
    }

    public function testGetData()
    {
        $shoppingList = new ShoppingList();
        $this->context->data()->set('shoppingList', null, $shoppingList);

        $actual = $this->dataProvider->getData($this->context);

        $this->assertInstanceOf('\Oro\Bundle\LayoutBundle\Layout\Form\FormAccessorInterface', $actual);
        $this->assertSame($this->dataProvider->getForm($shoppingList), $actual->getForm());
        $this->assertEquals(ShoppingListType::NAME, $actual->getForm()->getName());
    }
}
