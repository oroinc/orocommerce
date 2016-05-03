<?php

namespace OroB2B\Bundle\ShippingBundle\Tests\Unit\Form\Type;

use Symfony\Component\Form\Test\FormIntegrationTestCase;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

use Oro\Bundle\FormBundle\Form\Type\CollectionType;

use OroB2B\Bundle\ShippingBundle\Form\Type\ProductShippingOptionsType;
use OroB2B\Bundle\ShippingBundle\Form\Type\ProductShippingOptionsCollectionType;

class ProductShippingOptionsCollectionTypeTest extends FormIntegrationTestCase
{
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
    }

    public function testSetDefaultOptions()
    {
        /* @var $resolver \PHPUnit_Framework_MockObject_MockObject|OptionsResolverInterface */
        $resolver = $this->getMock('Symfony\Component\OptionsResolver\OptionsResolverInterface');
        $resolver->expects($this->once())
            ->method('setDefaults')
            ->with([
                'type' => ProductShippingOptionsType::NAME,
                'show_form_when_empty' => false,
                'error_bubbling' => false,
            ])
        ;

        $this->formType->setDefaultOptions($resolver);
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
