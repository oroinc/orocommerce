<?php

namespace OroB2B\Bundle\RFPAdminBundle\Tests\Unit\Form\Type;

use Symfony\Component\Form\Test\FormIntegrationTestCase;

use OroB2B\Bundle\RFPAdminBundle\Form\Type\RequestProductType;
use OroB2B\Bundle\RFPAdminBundle\Form\Type\RequestProductCollectionType;

class RequestProductCollectionTypeTest extends FormIntegrationTestCase
{
    /**
     * @var RequestProductCollectionType
     */
    protected $type;

    protected function setUp()
    {
        $this->type = new RequestProductCollectionType();
    }

    public function testSetDefaultOptions()
    {
        $resolver = $this->getMock('Symfony\Component\OptionsResolver\OptionsResolverInterface');
        $resolver->expects($this->once())
            ->method('setDefaults')
            ->with([
                'type'  => RequestProductType::NAME,
                'show_form_when_empty' => false,
                'prototype_name'       => '__namerequestproduct__',
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
        $this->assertEquals(RequestProductCollectionType::NAME, $this->type->getName());
    }
}
