<?php

namespace Oro\Bundle\TaxBundle\Tests\Unit\Form\Type;

use Symfony\Component\OptionsResolver\OptionsResolver;

use Oro\Bundle\FormBundle\Form\Type\CollectionType;
use Oro\Component\Testing\Unit\FormIntegrationTestCase;
use Oro\Bundle\TaxBundle\Form\Type\ZipCodeCollectionType;
use Oro\Bundle\TaxBundle\Form\Type\ZipCodeType;

class ZipCodeCollectionTypeTest extends FormIntegrationTestCase
{
    /**
     * @var ZipCodeCollectionType
     */
    protected $formType;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        parent::setUp();

        $this->formType = new ZipCodeCollectionType();
    }

    /**
     * {@inheritdoc}
     */
    protected function tearDown()
    {
        unset($this->formType);

        parent::tearDown();
    }

    public function testGetName()
    {
        $this->assertInternalType('string', $this->formType->getName());
        $this->assertEquals('oro_tax_zip_code_collection_type', $this->formType->getName());
    }

    public function testGetParent()
    {
        $this->assertInternalType('string', $this->formType->getParent());
        $this->assertEquals(CollectionType::NAME, $this->formType->getParent());
    }

    public function testSetDefaultOptions()
    {
        /* @var $resolver \PHPUnit_Framework_MockObject_MockObject|OptionsResolver */
        $resolver = $this->getMock('\Symfony\Component\OptionsResolver\OptionsResolver');
        $resolver->expects($this->once())
            ->method('setDefaults')
            ->with(
                [
                    'type' => ZipCodeType::NAME,
                    'required' => false,
                ]
            );

        $this->formType->configureOptions($resolver);
    }
}
