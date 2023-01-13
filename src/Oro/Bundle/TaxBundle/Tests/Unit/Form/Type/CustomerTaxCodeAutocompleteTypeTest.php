<?php

namespace Oro\Bundle\TaxBundle\Tests\Unit\Form\Type;

use Oro\Bundle\FormBundle\Form\Type\OroEntitySelectOrCreateInlineType;
use Oro\Bundle\TaxBundle\Form\Type\CustomerTaxCodeAutocompleteType;
use Oro\Component\Testing\Unit\FormIntegrationTestCase;
use Symfony\Component\OptionsResolver\OptionsResolver;

class CustomerTaxCodeAutocompleteTypeTest extends FormIntegrationTestCase
{
    /** @var CustomerTaxCodeAutocompleteType */
    private $formType;

    protected function setUp(): void
    {
        parent::setUp();

        $this->formType = new CustomerTaxCodeAutocompleteType();
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
        $this->assertEquals('oro_customer_tax_code', $options['autocomplete_alias']);
        $this->assertArrayHasKey('grid_name', $options);
        $this->assertEquals('customers-tax-code-select-grid', $options['grid_name']);
    }
}
