<?php

namespace OroB2B\Bundle\ProductBundle\Tests\Functional\Provider;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

use OroB2B\Bundle\ProductBundle\Form\Type\QuickAddType;
use OroB2B\Bundle\ProductBundle\Provider\QuickAddFormProvider;

class QuickAddFormProviderTest extends WebTestCase
{
    /** @var QuickAddFormProvider */
    protected $dataProvider;

    protected function setUp()
    {
        $this->initClient();

        $this->dataProvider = $this->getContainer()->get('orob2b_product.provider.quick_add_form_provider');
    }

    public function testGetForm()
    {
        $form = $this->dataProvider->getForm();

        $this->assertInstanceOf('\Symfony\Component\Form\FormInterface', $form);
        $this->assertEquals(QuickAddType::NAME, $form->getName());
    }
}
