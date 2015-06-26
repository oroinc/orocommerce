<?php

namespace OroB2B\Bundle\RFPAdminBundle\Tests\Unit\Form\Type;

use Symfony\Component\Form\Test\FormIntegrationTestCase;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

use Oro\Bundle\FormBundle\Form\Type\CollectionType;

use OroB2B\Bundle\RFPAdminBundle\Form\Type\RequestProductItemType;
use OroB2B\Bundle\RFPAdminBundle\Form\Type\RequestProductItemCollectionType;

class RequestProductItemCollectionTypeTest extends FormIntegrationTestCase
{
    /**
     * @var RequestProductItemCollectionType
     */
    protected $formType;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        parent::setUp();

        $this->formType = new RequestProductItemCollectionType();
    }

    public function testSetDefaultOptions()
    {
        /* @var $resolver \PHPUnit_Framework_MockObject_MockObject|OptionsResolverInterface */
        $resolver = $this->getMock('Symfony\Component\OptionsResolver\OptionsResolverInterface');
        $resolver->expects($this->once())
            ->method('setDefaults')
            ->with([
                'type'  => RequestProductItemType::NAME,
                'show_form_when_empty'  => false,
                'prototype_name'        => '__namerequestproductitem__',
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
        $this->assertEquals(RequestProductItemCollectionType::NAME, $this->formType->getName());
    }
}
