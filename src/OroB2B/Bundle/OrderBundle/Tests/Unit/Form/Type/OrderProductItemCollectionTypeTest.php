<?php

namespace OroB2B\Bundle\OrderBundle\Tests\Unit\Form\Type;

use Symfony\Component\Form\Test\FormIntegrationTestCase;
use Symfony\Component\OptionsResolver\OptionsResolver;

use Oro\Bundle\FormBundle\Form\Type\CollectionType;

use OroB2B\Bundle\OrderBundle\Form\Type\OrderProductItemType;
use OroB2B\Bundle\OrderBundle\Form\Type\OrderProductItemCollectionType;

class OrderProductItemCollectionTypeTest extends FormIntegrationTestCase
{
    /**
     * @var OrderProductItemCollectionType
     */
    protected $formType;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        parent::setUp();

        $this->formType = new OrderProductItemCollectionType();
    }

    public function testConfigureOptions()
    {
        /* @var $resolver OptionsResolver\PHPUnit_Framework_MockObject_MockObject */
        $resolver = $this->getMock('Symfony\Component\OptionsResolver\OptionsResolver');
        $resolver->expects($this->once())
            ->method('setDefaults')
            ->with([
                'type' => OrderProductItemType::NAME,
                'show_form_when_empty' => false,
                'error_bubbling' => false,
                'prototype_name' => '__nameorderproductitem__',
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
        $this->assertEquals(OrderProductItemCollectionType::NAME, $this->formType->getName());
    }
}
