<?php

namespace OroB2B\Bundle\ProductBundle\Tests\Functional\Layout\DataProvider;

use Oro\Component\Layout\LayoutContext;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

use OroB2B\Bundle\ProductBundle\Entity\Product;
use OroB2B\Bundle\ProductBundle\Form\Type\FrontendLineItemType;
use OroB2B\Bundle\ProductBundle\Layout\DataProvider\ProductLineItemFormProvider;
use OroB2B\Bundle\ProductBundle\Model\ProductLineItem;

class ProductLineItemFormProviderTest extends WebTestCase
{
    /** @var ProductLineItemFormProvider */
    protected $dataProvider;


    protected function setUp()
    {
        $this->initClient();

        $this->dataProvider = new ProductLineItemFormProvider($this->getContainer()->get('form.factory'));
    }

    /**
     * @dataProvider getDataDataProvider
     *
     * @param true $isProduct
     */
    public function testGetData($isProduct)
    {
        $context = new LayoutContext();
        $product = null;
        if ($isProduct) {
            $product = new Product();
            $context->data()->set('product', null, $product);
        }
        $actual = $this->dataProvider->getData($context);
        $form = $actual->getForm();

        $this->assertInstanceOf('\Oro\Bundle\LayoutBundle\Layout\Form\FormAccessorInterface', $actual);
        $this->assertSame($this->dataProvider->getForm($product), $form);
        $lineItem = $form->getData();
        $this->assertLineItem($product, $lineItem);
        $this->assertEquals(FrontendLineItemType::NAME, $actual->getForm()->getName());
    }

    /**
     * @return array
     */
    public function getDataDataProvider()
    {
        return [
            ['isProduct' => true],
            ['isProduct' => false],
        ];
    }

    /**
     * @param Product|null $product
     * @param ProductLineItem|null $lineItem
     */
    protected function assertLineItem(Product $product = null, ProductLineItem $lineItem = null)
    {
        $this->assertInstanceOf('OroB2B\Bundle\ProductBundle\Model\ProductLineItem', $lineItem);
        $this->assertSame($product, $lineItem->getProduct());
    }
}
