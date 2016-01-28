<?php

namespace OroB2B\Bundle\ProductBundle\Tests\Functional\Provider;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Component\Layout\LayoutContext;

use OroB2B\Bundle\ProductBundle\Form\Type\QuickAddType;
use OroB2B\Bundle\ProductBundle\Provider\QuickAddFormProvider;

class QuickAddFormProviderTest extends WebTestCase
{
    /** @var LayoutContext */
    protected $context;

    /** @var QuickAddFormProvider */
    protected $dataProvider;

    protected function setUp()
    {
        $this->initClient();

        $this->context = new LayoutContext();
        $this->dataProvider = $this->getContainer()->get('orob2b_product.provider.quick_add_form_provider');
    }

    public function testGetIdentifier()
    {
        $this->assertEquals('orob2b_product_quick_add_form_provider', $this->dataProvider->getIdentifier());
    }

    public function testGetData()
    {
        $actual = $this->dataProvider->getData($this->context);

        $this->assertInstanceOf('\Oro\Bundle\LayoutBundle\Layout\Form\FormAccessorInterface', $actual);
        $this->assertSame($this->dataProvider->getForm(), $actual->getForm());
        $this->assertEquals(QuickAddType::NAME, $actual->getForm()->getName());
    }
}
