<?php

namespace OroB2B\Bundle\TaxBundle\Tests\Unit\Form\Type;

use Symfony\Component\Form\Test\FormIntegrationTestCase;
use Symfony\Component\OptionsResolver\OptionsResolver;

use OroB2B\Bundle\TaxBundle\Form\Type\AccountGroupTaxCodeAutocompleteType;

class AccountGroupTaxCodeAutocompleteTypeTest extends FormIntegrationTestCase
{
    const DATA_CLASS = 'OroB2B\Bundle\TaxBundle\Entity\AccountGroupTaxCode';

    /**
     * @var AccountGroupTaxCodeAutocompleteType
     */
    protected $formType;

    protected function setUp()
    {
        parent::setUp();

        $this->formType = $this->createTaxCodeAutocompleteType();
    }

    /**
     * @return AccountGroupTaxCodeAutocompleteType
     */
    protected function createTaxCodeAutocompleteType()
    {
        return new AccountGroupTaxCodeAutocompleteType();
    }

    public function testGetName()
    {
        $this->assertEquals('orob2b_account_group_tax_code_autocomplete', $this->formType->getName());
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
        $this->assertEquals('orob2b_account_group_tax_code', $options['autocomplete_alias']);
        $this->assertArrayHasKey('grid_name', $options);
        $this->assertEquals('account-groups-tax-code-select-grid', $options['grid_name']);
    }

    /**
     * @return string
     */
    protected function getDataClass()
    {
        return self::DATA_CLASS;
    }
}
