<?php

namespace OroB2B\Bundle\TaxBundle\Tests\Unit\Form\Type;

use Symfony\Component\Form\Test\FormIntegrationTestCase;

use OroB2B\Bundle\TaxBundle\Form\Type\ProductTaxCodeAutocompleteType;

class ProductTaxCodeAutocompleteTypeTest extends FormIntegrationTestCase
{
    const DATA_CLASS = 'OroB2B\Bundle\TaxBundle\Entity\ProductTaxCode';

    /**
     * @var ProductTaxCodeAutocompleteType
     */
    protected $formType;

    protected function setUp()
    {
        parent::setUp();

        $this->formType = $this->createTaxCodeAutocompleteType();
    }

    /**
     * {@inheritdoc}
     */
    protected function createTaxCodeAutocompleteType()
    {
        return new ProductTaxCodeAutocompleteType();
    }

    /**
     * {@inheritdoc}
     */
    public function testGetName()
    {
        $this->assertEquals('orob2b_product_tax_code_autocomplete', $this->formType->getName());
    }

    /**
     * {@inheritdoc}
     */
    protected function getDataClass()
    {
        return self::DATA_CLASS;
    }
}
