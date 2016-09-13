<?php

namespace Oro\Bundle\TaxBundle\Tests\Unit\Form\Type;

use Symfony\Component\OptionsResolver\OptionsResolver;

use Oro\Component\Testing\Unit\FormIntegrationTestCase;
use Oro\Bundle\TaxBundle\Form\Type\ProductTaxCodeMultiSelectType;

class ProductTaxCodeMultiSelectTypeTest extends FormIntegrationTestCase
{
    /** @var ProductTaxCodeMultiSelectType */
    protected $formType;

    protected function setUp()
    {
        $this->formType = new ProductTaxCodeMultiSelectType();

        parent::setUp();
    }

    protected function tearDown()
    {
        unset($this->formType);

        parent::tearDown();
    }

    public function testGetName()
    {
        $this->assertEquals('oro_tax_product_tax_code_multiselect', $this->formType->getName());
    }

    public function testGetParent()
    {
        $this->assertEquals('oro_jqueryselect2_hidden', $this->formType->getParent());
    }

    public function testConfigureOptions()
    {
        $resolver = new OptionsResolver();
        $this->formType->configureOptions($resolver);
        $options = $resolver->resolve();

        $this->assertArrayHasKey('autocomplete_alias', $options);
        $this->assertEquals('oro_product_tax_code', $options['autocomplete_alias']);
        $this->assertArrayHasKey('configs', $options);
        $this->assertEquals(['multiple' => true, 'forceSelectedData' => true], $options['configs']);
    }
}
