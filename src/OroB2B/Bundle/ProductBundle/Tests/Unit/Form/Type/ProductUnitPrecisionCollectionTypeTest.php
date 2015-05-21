<?php

namespace OroB2B\Bundle\ProductBundle\Tests\Unit\Form\Type;

use OroB2B\Bundle\ProductBundle\Form\Type\ProductUnitPrecisionCollectionType;
use OroB2B\Bundle\ProductBundle\Form\Type\ProductUnitPrecisionType;

class ProductUnitPrecisionCollectionTypeTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var ProductUnitPrecisionCollectionType
     */
    protected $formType;

    /**
     * @inheritdoc
     */
    protected function setUp()
    {
        $this->formType = new ProductUnitPrecisionCollectionType();
    }

    public function testSetDefaultOptions()
    {
        $resolver = $this->getMock('Symfony\Component\OptionsResolver\OptionsResolverInterface');
        $resolver->expects($this->once())
            ->method('setDefaults')
            ->with([
                'type' => ProductUnitPrecisionType::NAME,
                'show_form_when_empty' => false
            ]);

        $this->formType->setDefaultOptions($resolver);
    }

    public function testGetParent()
    {
        $this->assertEquals('oro_collection', $this->formType->getParent());
    }

    public function testGetName()
    {
        $this->assertEquals(ProductUnitPrecisionCollectionType::NAME, $this->formType->getName());
    }
}
