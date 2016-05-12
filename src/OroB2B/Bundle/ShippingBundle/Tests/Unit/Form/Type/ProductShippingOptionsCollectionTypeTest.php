<?php

namespace OroB2B\Bundle\ShippingBundle\Tests\Unit\Form\Type;

use Symfony\Component\Form\Test\FormIntegrationTestCase;
use Symfony\Component\OptionsResolver\OptionsResolver;

use Oro\Bundle\FormBundle\Form\Type\CollectionType;

use OroB2B\Bundle\ShippingBundle\Form\Type\ProductShippingOptionsCollectionType;
use OroB2B\Bundle\ShippingBundle\Form\Type\ProductShippingOptionsType;

class ProductShippingOptionsCollectionTypeTest extends FormIntegrationTestCase
{
    const DATA_CLASS = 'stdClass';

    /**
     * @var ProductShippingOptionsCollectionType
     */
    protected $formType;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        parent::setUp();

        $this->formType = new ProductShippingOptionsCollectionType();
        $this->formType->setDataClass(self::DATA_CLASS);
    }

    public function testSetDefaultOptions()
    {
        /* @var $resolver \PHPUnit_Framework_MockObject_MockObject|OptionsResolver */
        $resolver = $this->getMock('Symfony\Component\OptionsResolver\OptionsResolver');
        $resolver->expects($this->once())
            ->method('setDefaults')
            ->with([
                'type' => ProductShippingOptionsType::NAME,
                'show_form_when_empty' => false,
                'options' => [
                    'data_class' => self::DATA_CLASS
                ]
            ])
        ;

        $this->formType->configureOptions($resolver);
    }

    public function testGetParent()
    {
        $this->assertEquals(CollectionType::NAME, $this->formType->getParent());
    }

    public function testGetName()
    {
        $this->assertEquals(ProductShippingOptionsCollectionType::NAME, $this->formType->getName());
    }
}
