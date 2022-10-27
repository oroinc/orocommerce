<?php

namespace Oro\Bundle\TaxBundle\Tests\Unit\Form\Type;

use Oro\Bundle\FormBundle\Form\Type\CollectionType;
use Oro\Bundle\TaxBundle\Form\Type\TaxBaseExclusionCollectionType;
use Oro\Bundle\TaxBundle\Form\Type\TaxBaseExclusionType;
use Oro\Component\Testing\Unit\FormIntegrationTestCase;
use Symfony\Component\OptionsResolver\OptionsResolver;

class TaxBaseExclusionCollectionTypeTest extends FormIntegrationTestCase
{
    /** @var TaxBaseExclusionCollectionType */
    private $formType;

    protected function setUp(): void
    {
        $this->formType = new TaxBaseExclusionCollectionType();

        parent::setUp();
    }

    public function testGetName()
    {
        $this->assertEquals('oro_tax_base_exclusion_collection', $this->formType->getName());
    }

    public function testGetParent()
    {
        $this->assertEquals(CollectionType::class, $this->formType->getParent());
    }

    public function testConfigureOptions()
    {
        $resolver = new OptionsResolver();
        $this->formType->configureOptions($resolver);
        $options = $resolver->resolve();

        $this->assertArrayHasKey('entry_type', $options);
        $this->assertEquals(TaxBaseExclusionType::class, $options['entry_type']);
        $this->assertArrayHasKey('show_form_when_empty', $options);
        $this->assertFalse($options['show_form_when_empty']);
    }
}
