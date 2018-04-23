<?php

namespace Oro\Bundle\RFPBundle\Tests\Unit\Form\Type;

use Oro\Bundle\FormBundle\Form\Type\CollectionType;
use Oro\Bundle\RFPBundle\Form\Type\RequestProductCollectionType;
use Oro\Bundle\RFPBundle\Form\Type\RequestProductType;
use Symfony\Component\Form\Test\FormIntegrationTestCase;
use Symfony\Component\OptionsResolver\OptionsResolver;

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
        $resolver = $this->createMock('Symfony\Component\OptionsResolver\OptionsResolver');
        $resolver->expects($this->once())
            ->method('setDefaults')
            ->with([
                'entry_type' => RequestProductType::class,
                'show_form_when_empty'  => true,
                'error_bubbling'        => false,
                'prototype_name'        => '__namerequestproduct__',
            ])
        ;

        $this->formType->configureOptions($resolver);
    }

    public function testGetParent()
    {
        $this->assertEquals(CollectionType::class, $this->formType->getParent());
    }

    public function testGetName()
    {
        $this->assertEquals(RequestProductCollectionType::NAME, $this->formType->getName());
    }
}
