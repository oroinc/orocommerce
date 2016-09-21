<?php

namespace Oro\Bundle\TaxBundle\Tests\Unit\Form\Type;

use Symfony\Component\OptionsResolver\OptionsResolver;

use Oro\Component\Testing\Unit\FormIntegrationTestCase;
use Oro\Bundle\TaxBundle\Form\Type\TaxBaseExclusionCollectionType;

class TaxBaseExclusionCollectionTypeTest extends FormIntegrationTestCase
{
    /** @var TaxBaseExclusionCollectionType */
    protected $formType;

    protected function setUp()
    {
        $this->formType = new TaxBaseExclusionCollectionType();

        parent::setUp();
    }

    protected function tearDown()
    {
        unset($this->formType);

        parent::tearDown();
    }

    public function testGetName()
    {
        $this->assertEquals('oro_tax_base_exclusion_collection', $this->formType->getName());
    }

    public function testGetParent()
    {
        $this->assertEquals('oro_collection', $this->formType->getParent());
    }

    public function testConfigureOptions()
    {
        $resolver = new OptionsResolver();
        $this->formType->configureOptions($resolver);
        $options = $resolver->resolve();

        $this->assertArrayHasKey('type', $options);
        $this->assertEquals('oro_tax_base_exclusion', $options['type']);
        $this->assertArrayHasKey('show_form_when_empty', $options);
        $this->assertFalse($options['show_form_when_empty']);
    }
}
