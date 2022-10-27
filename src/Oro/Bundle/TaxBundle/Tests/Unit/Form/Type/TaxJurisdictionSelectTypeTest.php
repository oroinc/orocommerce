<?php

namespace Oro\Bundle\TaxBundle\Tests\Unit\Form\Type;

use Oro\Bundle\FormBundle\Form\Type\OroEntitySelectOrCreateInlineType;
use Oro\Bundle\TaxBundle\Form\Type\TaxJurisdictionSelectType;
use Symfony\Component\OptionsResolver\OptionsResolver;

class TaxJurisdictionSelectTypeTest extends \PHPUnit\Framework\TestCase
{
    /** @var TaxJurisdictionSelectType */
    private $type;

    protected function setUp(): void
    {
        $this->type = new TaxJurisdictionSelectType();
    }

    public function testGetParent()
    {
        $this->assertEquals(OroEntitySelectOrCreateInlineType::class, $this->type->getParent());
    }

    public function testConfigureOptions()
    {
        $resolver = new OptionsResolver();

        $this->type->configureOptions($resolver);
        $options = $resolver->resolve([]);

        $this->assertArrayHasKey('autocomplete_alias', $options);
        $this->assertArrayHasKey('create_form_route', $options);
        $this->assertArrayHasKey('configs', $options);
        $this->assertEquals('oro_tax_jurisdiction_autocomplete', $options['autocomplete_alias']);
        $this->assertEquals('oro_tax_jurisdiction_create', $options['create_form_route']);
        $this->assertEquals('tax-jurisdiction-select-grid', $options['grid_name']);
        $this->assertEquals(
            ['placeholder' => 'oro.tax.taxjurisdiction.form.choose'],
            $options['configs']
        );
    }
}
