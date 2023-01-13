<?php

namespace Oro\Bundle\TaxBundle\Tests\Unit\Form\Type;

use Oro\Bundle\FormBundle\Form\Type\CollectionType;
use Oro\Bundle\TaxBundle\Form\Type\ZipCodeCollectionType;
use Oro\Bundle\TaxBundle\Form\Type\ZipCodeType;
use Oro\Component\Testing\Unit\FormIntegrationTestCase;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ZipCodeCollectionTypeTest extends FormIntegrationTestCase
{
    /** @var ZipCodeCollectionType */
    private $formType;

    protected function setUp(): void
    {
        parent::setUp();

        $this->formType = new ZipCodeCollectionType();
    }

    public function testGetParent()
    {
        $this->assertIsString($this->formType->getParent());
        $this->assertEquals(CollectionType::class, $this->formType->getParent());
    }

    public function testConfigureOptions()
    {
        $resolver = $this->createMock(OptionsResolver::class);
        $resolver->expects($this->once())
            ->method('setDefaults')
            ->with(['entry_type' => ZipCodeType::class, 'required' => false]);

        $this->formType->configureOptions($resolver);
    }
}
