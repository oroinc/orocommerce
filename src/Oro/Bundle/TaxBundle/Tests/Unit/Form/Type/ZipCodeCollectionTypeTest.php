<?php

namespace Oro\Bundle\TaxBundle\Tests\Unit\Form\Type;

use Oro\Bundle\FormBundle\Form\Type\CollectionType;
use Oro\Bundle\TaxBundle\Form\Type\ZipCodeCollectionType;
use Oro\Bundle\TaxBundle\Form\Type\ZipCodeType;
use Oro\Component\Testing\Unit\FormIntegrationTestCase;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ZipCodeCollectionTypeTest extends FormIntegrationTestCase
{
    /**
     * @var ZipCodeCollectionType
     */
    protected $formType;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->formType = new ZipCodeCollectionType();
    }

    /**
     * {@inheritdoc}
     */
    protected function tearDown(): void
    {
        unset($this->formType);

        parent::tearDown();
    }

    public function testGetName()
    {
        $this->assertIsString($this->formType->getName());
        $this->assertEquals('oro_tax_zip_code_collection_type', $this->formType->getName());
    }

    public function testGetParent()
    {
        $this->assertIsString($this->formType->getParent());
        $this->assertEquals(CollectionType::class, $this->formType->getParent());
    }

    public function testConfigureOptions()
    {
        /* @var $resolver \PHPUnit\Framework\MockObject\MockObject|OptionsResolver */
        $resolver = $this->createMock('\Symfony\Component\OptionsResolver\OptionsResolver');
        $resolver->expects($this->once())
            ->method('setDefaults')
            ->with(
                [
                    'entry_type' => ZipCodeType::class,
                    'required' => false,
                ]
            );

        $this->formType->configureOptions($resolver);
    }
}
