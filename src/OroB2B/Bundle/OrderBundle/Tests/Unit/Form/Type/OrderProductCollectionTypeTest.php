<?php

namespace OroB2B\Bundle\OrderBundle\Tests\Unit\Form\Type;

use Symfony\Component\Form\Test\FormIntegrationTestCase;
use Symfony\Component\OptionsResolver\OptionsResolver;

use Oro\Bundle\FormBundle\Form\Type\CollectionType;

use OroB2B\Bundle\OrderBundle\Form\Type\OrderProductType;
use OroB2B\Bundle\OrderBundle\Form\Type\OrderProductCollectionType;

class OrderProductCollectionTypeTest extends FormIntegrationTestCase
{
    /**
     * @var OrderProductCollectionType
     */
    protected $formType;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        parent::setUp();

        $this->formType = new OrderProductCollectionType();
    }

    public function testConfigureOptions()
    {
        /* @var $resolver OptionsResolver|\PHPUnit_Framework_MockObject_MockObject */
        $resolver = $this->getMock('Symfony\Component\OptionsResolver\OptionsResolver');
        $resolver->expects($this->once())
            ->method('setDefaults')
            ->with([
                'type'  => OrderProductType::NAME,
                'show_form_when_empty'  => false,
                'error_bubbling'        => false,
                'prototype_name'        => '__nameorderproduct__',
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
        $this->assertEquals(OrderProductCollectionType::NAME, $this->formType->getName());
    }
}
