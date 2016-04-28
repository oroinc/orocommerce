<?php

namespace OroB2B\Bundle\ProductBundle\Tests\Functional\Layout\DataProvider;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Component\Layout\LayoutContext;

use OroB2B\Bundle\ProductBundle\Form\Type\QuickAddCopyPasteType;
use OroB2B\Bundle\ProductBundle\Layout\DataProvider\QuickAddCopyPasteFormProvider;

class QuickAddCopyPasteFormProviderTest extends WebTestCase
{
    /** @var LayoutContext */
    protected $context;

    /** @var QuickAddCopyPasteFormProvider */
    protected $dataProvider;

    protected function setUp()
    {
        $this->initClient();

        $this->context = new LayoutContext();
        $this->dataProvider = new QuickAddCopyPasteFormProvider(
            $this->getContainer()->get('form.factory')
        );
    }

    public function testGetIdentifier()
    {
        $this->assertEquals('orob2b_product_quick_add_copy_paste_form_provider', $this->dataProvider->getIdentifier());
    }

    public function testGetData()
    {
        $actual = $this->dataProvider->getData($this->context);

        $this->assertInstanceOf('\Oro\Bundle\LayoutBundle\Layout\Form\FormAccessorInterface', $actual);
        $this->assertSame($this->dataProvider->getForm(), $actual->getForm());
        $this->assertEquals(QuickAddCopyPasteType::NAME, $actual->getForm()->getName());
    }
}
