<?php

namespace Oro\Bundle\TaxBundle\Tests\Unit\Form\Type;

use Oro\Bundle\FormBundle\Form\Type\OroJquerySelect2HiddenType;
use Oro\Bundle\TaxBundle\Form\Type\ProductTaxCodeMultiSelectType;
use Oro\Component\Testing\Unit\FormIntegrationTestCase;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ProductTaxCodeMultiSelectTypeTest extends FormIntegrationTestCase
{
    /** @var ProductTaxCodeMultiSelectType */
    private $formType;

    protected function setUp(): void
    {
        $this->formType = new ProductTaxCodeMultiSelectType();

        parent::setUp();
    }

    public function testGetParent()
    {
        $this->assertEquals(OroJquerySelect2HiddenType::class, $this->formType->getParent());
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
