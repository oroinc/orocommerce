<?php

namespace OroB2B\Bundle\RFPAdminBundle\Tests\Unit\Form\Type;

use Symfony\Component\Form\Test\FormIntegrationTestCase;

use OroB2B\Bundle\RFPAdminBundle\Form\Type\RequestProductItemType;
use OroB2B\Bundle\RFPAdminBundle\Form\Type\RequestProductItemCollectionType;

class RequestProductItemCollectionTypeTest extends FormIntegrationTestCase
{
    /**
     * @var RequestProductItemCollectionType
     */
    protected $type;

    protected function setUp()
    {
        $this->type = new RequestProductItemCollectionType();
    }

    public function testSetDefaultOptions()
    {
        $resolver = $this->getMock('Symfony\Component\OptionsResolver\OptionsResolverInterface');
        $resolver->expects($this->once())
            ->method('setDefaults')
            ->with([
                'type'  => RequestProductItemType::NAME,
                'show_form_when_empty' => false,
                'prototype_name'       => '__namerequestproductitem__',
            ])
        ;

        $this->type->setDefaultOptions($resolver);
    }

    public function testGetParent()
    {
        $this->assertEquals('oro_collection', $this->type->getParent());
    }

    public function testGetName()
    {
        $this->assertEquals(RequestProductItemCollectionType::NAME, $this->type->getName());
    }
}
