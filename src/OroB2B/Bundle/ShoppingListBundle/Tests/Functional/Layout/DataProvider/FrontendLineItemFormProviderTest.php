<?php

namespace OroB2B\Bundle\ShoppingListBundle\Tests\Functional\Layout\DataProvider;

use Oro\Component\Layout\LayoutContext;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

use OroB2B\Bundle\ProductBundle\Entity\Product;
use OroB2B\Bundle\ShoppingListBundle\Form\Type\FrontendLineItemType;
use OroB2B\Bundle\ShoppingListBundle\Layout\DataProvider\FrontendLineItemFormProvider;

class FrontendLineItemFormProviderTest extends WebTestCase
{
    /** @var LayoutContext */
    protected $context;

    /** @var FrontendLineItemFormProvider */
    protected $dataProvider;

    protected function setUp()
    {
        $this->initClient();

        $this->context = new LayoutContext();
        $this->dataProvider = $this->getContainer()
            ->get('orob2b_shopping_list.layout.data_provider.frontend_line_item_form');
    }

    public function testGetData()
    {
        $product = new Product();
        $this->context->data()->set('product', null, $product);

        $actual = $this->dataProvider->getData($this->context);

        $this->assertInstanceOf('\Oro\Bundle\LayoutBundle\Layout\Form\FormAccessorInterface', $actual);
        $this->assertSame($this->dataProvider->getForm($product), $actual->getForm());
        $this->assertEquals(FrontendLineItemType::NAME, $actual->getForm()->getName());
    }
}
