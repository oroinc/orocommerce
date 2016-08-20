<?php

namespace Oro\Bundle\TaxBundle\Tests\Unit\Form\Type;

use Symfony\Component\OptionsResolver\OptionsResolver;

use Oro\Bundle\FormBundle\Form\Type\OroEntitySelectOrCreateInlineType;
use Oro\Bundle\TaxBundle\Form\Type\TaxJurisdictionSelectType;

class TaxJurisdictionSelectTypeTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var TaxJurisdictionSelectType
     */
    protected $type;

    protected function setUp()
    {
        $this->type = new TaxJurisdictionSelectType();
    }

    protected function tearDown()
    {
        unset($this->type);
    }

    public function testGetName()
    {
        $this->assertEquals(TaxJurisdictionSelectType::NAME, $this->type->getName());
    }

    public function testGetParent()
    {
        $this->assertEquals(OroEntitySelectOrCreateInlineType::NAME, $this->type->getParent());
    }

    public function testConfigureOptions()
    {
        $resolver = new OptionsResolver();

        $this->type->configureOptions($resolver);
        $options = $resolver->resolve([]);

        $this->assertArrayHasKey('autocomplete_alias', $options);
        $this->assertArrayHasKey('create_form_route', $options);
        $this->assertArrayHasKey('configs', $options);
        $this->assertEquals('orob2b_tax_jurisdiction_autocomplete', $options['autocomplete_alias']);
        $this->assertEquals('orob2b_tax_jurisdiction_create', $options['create_form_route']);
        $this->assertEquals('tax-jurisdiction-select-grid', $options['grid_name']);
        $this->assertEquals(
            ['placeholder' => 'oro.tax.taxjurisdiction.form.choose'],
            $options['configs']
        );
    }
}
