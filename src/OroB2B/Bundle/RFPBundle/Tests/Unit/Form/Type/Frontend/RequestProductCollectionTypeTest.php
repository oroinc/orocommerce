<?php

namespace OroB2B\Bundle\RFPBundle\Tests\Unit\Form\Frontend\Type;

use Symfony\Component\Form\Test\FormIntegrationTestCase;
use Symfony\Component\OptionsResolver\OptionsResolver;

use Oro\Bundle\FormBundle\Form\Type\CollectionType;

use OroB2B\Bundle\RFPBundle\Form\Type\Frontend\RequestProductType;
use OroB2B\Bundle\RFPBundle\Form\Type\Frontend\RequestProductCollectionType;

class RequestProductCollectionTypeTest extends FormIntegrationTestCase
{
    /**
     * @var RequestProductCollectionType
     */
    protected $formType;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        parent::setUp();

        $this->formType = new RequestProductCollectionType();
    }

    public function testConfigureOptions()
    {
        /* @var $resolver \PHPUnit_Framework_MockObject_MockObject|OptionsResolver */
        $resolver = $this->getMock('Symfony\Component\OptionsResolver\OptionsResolver');
        $resolver->expects(static::once())
            ->method('setDefaults')
            ->with([
                'type' => RequestProductType::NAME,
                'show_form_when_empty'  => true,
                'error_bubbling'        => false,
                'prototype_name'        => '__namerequestproduct__',
            ])
        ;

        $this->formType->configureOptions($resolver);
    }

    public function testGetParent()
    {
        static::assertEquals(CollectionType::NAME, $this->formType->getParent());
    }

    public function testGetName()
    {
        static::assertEquals(RequestProductCollectionType::NAME, $this->formType->getName());
    }
}
