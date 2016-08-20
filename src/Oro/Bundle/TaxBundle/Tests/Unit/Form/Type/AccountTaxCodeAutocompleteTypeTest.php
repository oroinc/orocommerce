<?php

namespace Oro\Bundle\TaxBundle\Tests\Unit\Form\Type;

use Symfony\Component\OptionsResolver\OptionsResolver;

use Oro\Component\Testing\Unit\FormIntegrationTestCase;
use Oro\Bundle\TaxBundle\Form\Type\AccountTaxCodeAutocompleteType;

class AccountTaxCodeAutocompleteTypeTest extends FormIntegrationTestCase
{
    const DATA_CLASS = 'Oro\Bundle\TaxBundle\Entity\AccountTaxCode';

    /**
     * @var AccountTaxCodeAutocompleteType
     */
    protected $formType;

    protected function setUp()
    {
        parent::setUp();

        $this->formType = $this->createTaxCodeAutocompleteType();
    }

    protected function tearDown()
    {
        unset($this->formType);

        parent::tearDown();
    }

    /**
     * @return AccountTaxCodeAutocompleteType
     */
    protected function createTaxCodeAutocompleteType()
    {
        return new AccountTaxCodeAutocompleteType();
    }

    public function testGetName()
    {
        $this->assertEquals('orob2b_account_tax_code_autocomplete', $this->formType->getName());
    }

    public function testGetParent()
    {
        $this->assertEquals('oro_entity_create_or_select_inline', $this->formType->getParent());
    }

    public function testConfigureOptions()
    {
        $resolver = new OptionsResolver();
        $this->formType->configureOptions($resolver);
        $options = $resolver->resolve();

        $this->assertArrayHasKey('autocomplete_alias', $options);
        $this->assertEquals('orob2b_account_tax_code', $options['autocomplete_alias']);
        $this->assertArrayHasKey('grid_name', $options);
        $this->assertEquals('accounts-tax-code-select-grid', $options['grid_name']);
    }

    /**
     * @return string
     */
    protected function getDataClass()
    {
        return self::DATA_CLASS;
    }
}
