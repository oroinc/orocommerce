<?php

namespace OroB2B\Bundle\SaleBundle\Tests\Unit\Form\Type;

use Symfony\Component\Form\Test\FormIntegrationTestCase;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

use Oro\Bundle\FormBundle\Form\Type\CollectionType;

use OroB2B\Bundle\SaleBundle\Form\Type\QuoteProductType;
use OroB2B\Bundle\SaleBundle\Form\Type\QuoteProductCollectionType;

class QuoteProductCollectionTypeTest extends FormIntegrationTestCase
{
    /**
     * @var QuoteProductCollectionType
     */
    protected $formType;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        parent::setUp();

        $this->formType = new QuoteProductCollectionType();
    }

    public function testSetDefaultOptions()
    {
        /* @var $resolver \PHPUnit_Framework_MockObject_MockObject|OptionsResolverInterface */
        $resolver = $this->getMock('Symfony\Component\OptionsResolver\OptionsResolverInterface');
        $resolver->expects($this->once())
            ->method('setDefaults')
            ->with([
                    'type'  => QuoteProductType::NAME,
                    'show_form_when_empty'  => false,
                    'error_bubbling'        => false,
                    'prototype_name'        => '__namequoteproduct__',
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
        $this->assertEquals(QuoteProductCollectionType::NAME, $this->formType->getName());
    }
}
