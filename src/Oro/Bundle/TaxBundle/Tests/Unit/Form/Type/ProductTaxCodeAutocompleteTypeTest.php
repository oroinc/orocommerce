<?php

namespace Oro\Bundle\TaxBundle\Tests\Unit\Form\Type;

use Oro\Bundle\FormBundle\Form\Type\OroEntitySelectOrCreateInlineType;
use Oro\Bundle\TaxBundle\Form\Type\ProductTaxCodeAutocompleteType;
use Oro\Component\Testing\Unit\FormIntegrationTestCase;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ProductTaxCodeAutocompleteTypeTest extends FormIntegrationTestCase
{
    /** @var ProductTaxCodeAutocompleteType */
    private $formType;

    protected function setUp(): void
    {
        parent::setUp();

        $this->formType = new ProductTaxCodeAutocompleteType();
    }

    public function testGetParent()
    {
        $this->assertEquals(OroEntitySelectOrCreateInlineType::class, $this->formType->getParent());
    }

    public function testConfigureOptions()
    {
        $resolver = new OptionsResolver();
        $this->formType->configureOptions($resolver);
        $options = $resolver->resolve();

        $this->assertArrayHasKey('autocomplete_alias', $options);
        $this->assertEquals('oro_product_tax_code', $options['autocomplete_alias']);
        $this->assertArrayHasKey('grid_name', $options);
        $this->assertEquals('products-tax-code-select-grid', $options['grid_name']);
    }
}
